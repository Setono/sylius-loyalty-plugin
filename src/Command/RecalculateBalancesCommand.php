<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Command;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Re-derives every account's balance from the ledger and reports drift (which must always be
 * zero). Drift is only corrected with the explicit --force flag.
 */
#[AsCommand(
    name: 'setono:sylius-loyalty:recalculate-balances',
    description: 'Re-derives account balances from the ledger and reports drift',
)]
final class RecalculateBalancesCommand extends Command
{
    /**
     * @param class-string<LoyaltyAccountInterface> $accountClass
     */
    public function __construct(
        private readonly LoyaltyTransactionRepositoryInterface $transactionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $accountClass,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Accounts processed per batch', '100')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Write the derived balance where it drifted')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $batchSize = max(1, (int) $input->getOption('batch-size'));
        $force = (bool) $input->getOption('force');

        $accounts = 0;
        $drifted = 0;
        $offset = 0;
        while (true) {
            /** @var list<LoyaltyAccountInterface> $batch */
            $batch = $this->entityManager->createQueryBuilder()
                ->select('a')
                ->from($this->accountClass, 'a')
                ->orderBy('a.id', 'ASC')
                ->setFirstResult($offset)
                ->setMaxResults($batchSize)
                ->getQuery()
                ->getResult()
            ;

            if ([] === $batch) {
                break;
            }

            foreach ($batch as $account) {
                ++$accounts;
                $derived = $this->transactionRepository->sumPoints($account);
                if ($derived === $account->getBalance()) {
                    continue;
                }

                ++$drifted;
                $io->warning(sprintf(
                    'Account %s: cached balance %d, ledger sum %d (drift %+d)%s',
                    (string) $account->getId(),
                    $account->getBalance(),
                    $derived,
                    $account->getBalance() - $derived,
                    $force ? ' — corrected' : '',
                ));

                if ($force) {
                    $account->setBalance($derived);
                }
            }

            if ($force) {
                $this->entityManager->flush();
            }
            $this->entityManager->clear();
            $offset += $batchSize;
        }

        if ($drifted > 0 && !$force) {
            $io->error(sprintf('%d of %d account(s) drifted; run with --force to correct', $drifted, $accounts));

            return Command::FAILURE;
        }

        $io->success(sprintf('Checked %d account(s), %d drifted', $accounts, $drifted));

        return Command::SUCCESS;
    }
}
