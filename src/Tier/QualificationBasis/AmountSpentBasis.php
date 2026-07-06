<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tier\QualificationBasis;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Sylius\Component\Core\OrderPaymentStates;

/**
 * Sum of the customer's paid order totals (minor units) on the account's channel in the
 * window.
 */
final class AmountSpentBasis implements TierQualificationBasisInterface
{
    public const CODE = 'amount_spent';

    /**
     * @param class-string $orderClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $orderClass,
    ) {
    }

    public function getCode(): string
    {
        return self::CODE;
    }

    public function getLabel(): string
    {
        return 'setono_sylius_loyalty.tier_basis.amount_spent';
    }

    public function getUnitLabel(): string
    {
        return 'setono_sylius_loyalty.tier_basis.unit.currency';
    }

    public function calculate(LoyaltyAccountInterface $account, ?DateRange $window): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(o.total), 0)')
            ->from($this->orderClass, 'o')
            ->andWhere('o.customer = :customer')
            ->andWhere('o.channel = :channel')
            ->andWhere('o.paymentState = :paymentState')
            ->setParameter('customer', $account->getCustomer())
            ->setParameter('channel', $account->getChannel())
            ->setParameter('paymentState', OrderPaymentStates::STATE_PAID)
        ;

        if (null !== $window) {
            $qb->andWhere('o.checkoutCompletedAt >= :start')->setParameter('start', $window->start);
            $qb->andWhere('o.checkoutCompletedAt <= :end')->setParameter('end', $window->end);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
