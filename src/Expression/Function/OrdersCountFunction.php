<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression\Function;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;
use Sylius\Component\Core\OrderPaymentStates;

/**
 * The number of the customer's paid orders in the channel.
 */
final class OrdersCountFunction implements ExpressionFunctionInterface
{
    /**
     * @param class-string $orderClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $orderClass,
    ) {
    }

    public function getName(): string
    {
        return 'orders_count';
    }

    public function getSignature(): string
    {
        return 'orders_count(): int';
    }

    public function getDescription(): string
    {
        return 'setono_sylius_loyalty.expression.function.orders_count';
    }

    public function evaluate(EarningContext $context, mixed ...$arguments): mixed
    {
        if (null === $context->customer) {
            return 0;
        }

        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(o.id)')
            ->from($this->orderClass, 'o')
            ->andWhere('o.customer = :customer')
            ->andWhere('o.channel = :channel')
            ->andWhere('o.paymentState = :paymentState')
            ->setParameter('customer', $context->customer)
            ->setParameter('channel', $context->channel)
            ->setParameter('paymentState', OrderPaymentStates::STATE_PAID)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
