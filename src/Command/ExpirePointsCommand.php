<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Command;

use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'setono:loyalty:expire-points',
    description: 'Expires loyalty points whose lots have passed their expiry date',
)]
final class ExpirePointsCommand extends Command
{
    public function __construct(
        private readonly LoyaltyAccountRepositoryInterface $accountRepository,
        private readonly LoyaltyLedgerInterface $ledger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $asOf = new \DateTimeImmutable();

        $ids = $this->accountRepository->findIdsWithLotsExpiringAtOrBefore($asOf);

        foreach ($ids as $id) {
            $account = $this->accountRepository->find($id);
            if ($account instanceof LoyaltyAccountInterface) {
                $this->ledger->expire($account, $asOf);
            }
        }

        $io->success(sprintf('Expired points for %d account(s).', count($ids)));

        return Command::SUCCESS;
    }
}
