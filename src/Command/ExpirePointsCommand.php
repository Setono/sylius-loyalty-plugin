<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Command;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Writes expiration entries for lots past their expiry (run daily). Expiry runs for disabled
 * accounts too, so the liability doesn't freeze. Lots deferred by an ExpiringPoints listener
 * are re-selected on the next run.
 */
#[AsCommand(
    name: 'setono:sylius-loyalty:expire-points',
    description: 'Expires credit lots past their expiry date',
)]
final class ExpirePointsCommand extends Command
{
    /**
     * @param class-string<LoyaltyAccountInterface> $accountClass
     */
    public function __construct(
        private readonly LoyaltyTransactionRepositoryInterface $transactionRepository,
        private readonly LoyaltyLedgerInterface $ledger,
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

        // The candidate ids are collected up front: writing expirations removes accounts from
        // the selection, and lots deferred by listeners would otherwise be re-selected forever
        $accountIds = [];
        $offset = 0;
        do {
            $batch = $this->transactionRepository->findAccountIdsWithExpiredOpenLots($now, $batchSize, $offset);
            $accountIds = [...$accountIds, ...$batch];
            $offset += $batchSize;
        } while ([] !== $batch);

        $expired = 0;
        foreach ($accountIds as $index => $accountId) {
            $account = $this->entityManager->find($this->accountClass, $accountId);
            if ($account instanceof LoyaltyAccountInterface) {
                $expired += count($this->ledger->expire($account, $now));
            }

            if (0 === ($index + 1) % $batchSize) {
                $this->entityManager->clear();
            }
        }

        $io->success(sprintf('Wrote %d expiration entrie(s) across %d account(s)', $expired, count($accountIds)));

        return Command::SUCCESS;
    }
}
