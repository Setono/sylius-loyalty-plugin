<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\RedeemRollbackLoyaltyTransaction;

/**
 * Cheap ledger aggregates for the loyalty admin dashboard, plus the Phase 2 widgets:
 * liability comes from the scheduled snapshot on the program rows (never live replay),
 * redemption rate and active accounts are trailing-90-day ledger aggregates.
 */
final class DashboardStatsProvider implements DashboardStatsProviderInterface
{
    /**
     * @param class-string $accountClass
     * @param class-string $transactionClass
     * @param class-string $programClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $accountClass,
        private readonly string $transactionClass,
        private readonly string $programClass,
    ) {
    }

    public function getStats(): array
    {
        $since = new \DateTimeImmutable('-30 days');
        $quarter = new \DateTimeImmutable('-90 days');

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

        $earned90 = (int) $this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(c.points), 0)')
            ->from(CreditLoyaltyTransaction::class, 'c')
            ->andWhere('c.occurredAt >= :since')
            ->andWhere('c NOT INSTANCE OF :rollback')
            ->setParameter('since', $quarter)
            ->setParameter('rollback', $this->entityManager->getClassMetadata(RedeemRollbackLoyaltyTransaction::class))
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $redeemed90 = (int) $this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(-r.points), 0)')
            ->from(RedeemLoyaltyTransaction::class, 'r')
            ->andWhere('r.occurredAt >= :since')
            ->setParameter('since', $quarter)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $activeAccounts = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT IDENTITY(t.account))')
            ->from($this->transactionClass, 't')
            ->andWhere('t.occurredAt >= :since')
            ->setParameter('since', $quarter)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $liability = null;
        $liabilityCalculatedAt = null;
        /** @var list<LoyaltyProgramInterface> $programs */
        $programs = $this->entityManager->getRepository($this->programClass)->findAll();
        foreach ($programs as $program) {
            if (null !== $program->getLiabilityPoints()) {
                $liability = ($liability ?? 0) + $program->getLiabilityPoints();
                $calculatedAt = $program->getLiabilityCalculatedAt();
                if (null === $liabilityCalculatedAt || ($calculatedAt !== null && $calculatedAt < $liabilityCalculatedAt)) {
                    $liabilityCalculatedAt = $calculatedAt;
                }
            }
        }

        return [
            'accounts' => $accounts,
            'earnedLast30Days' => $earned,
            'redeemedLast30Days' => $redeemed,
            'activeAccounts90Days' => $activeAccounts,
            'redemptionRate90Days' => $earned90 > 0 ? round($redeemed90 / $earned90 * 100, 1) : null,
            'liabilityPoints' => $liability,
            'liabilityCalculatedAt' => $liabilityCalculatedAt,
        ];
    }
}
