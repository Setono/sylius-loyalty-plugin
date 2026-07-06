<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransaction;

/**
 * Cheap ledger aggregates for the loyalty admin dashboard. Phase 2 adds the liability and
 * redemption-rate widgets (computed by a scheduled aggregation, not here).
 */
final class DashboardStatsProvider implements DashboardStatsProviderInterface
{
    /**
     * @param class-string $accountClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $accountClass,
    ) {
    }

    public function getStats(): array
    {
        $since = new \DateTimeImmutable('-30 days');

        $accounts = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(a.id)')
            ->from($this->accountClass, 'a')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $earned = (int) $this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(c.points), 0)')
            ->from(CreditLoyaltyTransaction::class, 'c')
            ->andWhere('c.occurredAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $redeemed = (int) $this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(-r.points), 0)')
            ->from(RedeemLoyaltyTransaction::class, 'r')
            ->andWhere('r.occurredAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return [
            'accounts' => $accounts,
            'earnedLast30Days' => $earned,
            'redeemedLast30Days' => $redeemed,
        ];
    }
}
