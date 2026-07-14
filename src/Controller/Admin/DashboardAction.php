<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Controller\Admin;

use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * The admin loyalty dashboard: at-a-glance Phase 1 stats — the number of accounts, the outstanding
 * points liability, and the points earned/redeemed within a recent window.
 */
final class DashboardAction
{
    public function __construct(
        private readonly LoyaltyAccountRepositoryInterface $accountRepository,
        private readonly LoyaltyTransactionRepositoryInterface $transactionRepository,
        private readonly Environment $twig,
        private readonly int $windowDays = 30,
    ) {
    }

    public function __invoke(): Response
    {
        $since = new \DateTimeImmutable(sprintf('-%d days', $this->windowDays));

        return new Response($this->twig->render('@SetonoSyliusLoyaltyPlugin/admin/dashboard.html.twig', [
            'account_count' => $this->accountRepository->countAll(),
            'outstanding_balance' => $this->accountRepository->sumBalances(),
            'earned' => $this->transactionRepository->sumEarnedSince($since),
            'redeemed' => $this->transactionRepository->sumRedeemedSince($since),
            'window_days' => $this->windowDays,
        ]));
    }
}
