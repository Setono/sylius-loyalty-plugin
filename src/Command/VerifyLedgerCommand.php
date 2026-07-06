<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Command;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Inspector\AccountInspectorInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Verifies the ledger invariants for every account (cron-able). Violations are reported and
 * exit with a non-zero status — nothing is ever auto-fixed.
 */
#[AsCommand(
    name: 'setono:sylius-loyalty:verify-ledger',
    description: 'Verifies the ledger invariants for every loyalty account',
)]
final class VerifyLedgerCommand extends Command
{
    /**
     * @param class-string<LoyaltyAccountInterface> $accountClass
     */
    public function __construct(
        private readonly AccountInspectorInterface $accountInspector,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $accountClass,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Accounts processed per batch', '100');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $batchSize = max(1, (int) $input->getOption('batch-size'));
        $now = new \DateTimeImmutable();

        $accounts = 0;
        $errors = 0;
        $warnings = 0;
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
                $inspection = $this->accountInspector->inspect($account, $now);

                foreach ($inspection->errors as $error) {
                    ++$errors;
                    $io->error(sprintf('Account %s: %s', (string) $account->getId(), $error));
                }

                foreach ($inspection->warnings as $warning) {
                    ++$warnings;
                    $io->warning(sprintf('Account %s: %s', (string) $account->getId(), $warning));
                }
            }

            $this->entityManager->clear();
            $offset += $batchSize;
        }

        if ($errors > 0) {
            $io->error(sprintf('%d invariant violation(s) across %d account(s)', $errors, $accounts));

            return Command::FAILURE;
        }

        $io->success(sprintf('Verified %d account(s): no violations, %d warning(s)', $accounts, $warnings));

        return Command::SUCCESS;
    }
}
