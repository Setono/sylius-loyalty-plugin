<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Checker;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;
use Sylius\Component\Core\OrderPaymentStates;

/**
 * Passes when the order being evaluated is the customer's Nth paid order in the channel
 * (the order itself included), or every Nth when "every" is configured.
 */
final class NthOrderConditionChecker implements ConditionCheckerInterface
{
    public const TYPE = 'nth_order';

    /**
     * @param class-string $orderClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $orderClass,
    ) {
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return 'setono_sylius_loyalty.form.earning_rule.condition.nth_order';
    }

    public function getConfigurationFormType(): ?string
    {
        return null; // set when the admin forms ship
    }

    public function check(array $configuration, EarningContext $context): bool
    {
        if (null === $context->order || null === $context->customer) {
            return false;
        }

        $nth = $configuration['nth'] ?? null;
        if (!is_int($nth) || $nth < 1) {
            return false;
        }

        $position = $this->position($context);
        if (0 === $position) {
            return false;
        }

        if (true === ($configuration['every'] ?? false)) {
            return 0 === $position % $nth;
        }

        return $position === $nth;
    }

    /**
     * The 1-based position of the evaluated order among the customer's paid orders in the
     * channel. When the evaluated order is not itself counted as paid yet (e.g. in the rule
     * tester before payment), it is counted on top.
     */
    private function position(EarningContext $context): int
    {
        $paidOrders = (int) $this->entityManager->createQueryBuilder()
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

        $order = $context->order;
        if (null !== $order && OrderPaymentStates::STATE_PAID !== $order->getPaymentState()) {
            ++$paidOrders;
        }

        return $paidOrders;
    }
}
