<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tier\QualificationBasis;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\RedeemRollbackLoyaltyTransaction;

/**
 * Sum of qualifying credits in the window — every credit except redemption rollbacks, the
 * same definition as the account's lifetimeEarned, so manual goodwill credits count.
 */
final class PointsEarnedBasis implements TierQualificationBasisInterface
{
    public const CODE = 'points_earned';

    /**
     * @param class-string $transactionClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $transactionClass,
    ) {
    }

    public function getCode(): string
    {
        return self::CODE;
    }

    public function getLabel(): string
    {
        return 'setono_sylius_loyalty.tier_basis.points_earned';
    }

    public function getUnitLabel(): string
    {
        return 'setono_sylius_loyalty.tier_basis.unit.points';
    }

    public function calculate(LoyaltyAccountInterface $account, ?DateRange $window): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(t.points), 0)')
            ->from($this->transactionClass, 't')
            ->andWhere('t.account = :account')
            ->andWhere('t.points > 0')
            ->andWhere('t NOT INSTANCE OF :rollback')
            ->setParameter('account', $account)
            ->setParameter('rollback', $this->entityManager->getClassMetadata(RedeemRollbackLoyaltyTransaction::class))
        ;

        if (null !== $window) {
            $qb->andWhere('t.occurredAt >= :start')->setParameter('start', $window->start);
            $qb->andWhere('t.occurredAt < :end')->setParameter('end', $window->end);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
