<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression\Function;

use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;
use Sylius\Component\Core\OrderPaymentStates;

/**
 * Whether the order being evaluated is the customer's first paid order in the channel.
 */
final class IsFirstOrderFunction implements ExpressionFunctionInterface
{
    public function __construct(
        private readonly OrdersCountFunction $ordersCount,
    ) {
    }

    public function getName(): string
    {
        return 'is_first_order';
    }

    public function getSignature(): string
    {
        return 'is_first_order(): bool';
    }

    public function getDescription(): string
    {
        return 'setono_sylius_loyalty.expression.function.is_first_order';
    }

    public function evaluate(EarningContext $context, mixed ...$arguments): mixed
    {
        if (null === $context->customer) {
            return false;
        }

        $count = $this->ordersCount->evaluate($context);
        $paidOrders = is_int($count) ? $count : 0;

        // When the evaluated order is not itself counted as paid yet, it is counted on top
        if (null !== $context->order && OrderPaymentStates::STATE_PAID !== $context->order->getPaymentState()) {
            ++$paidOrders;
        }

        return 1 === $paidOrders;
    }
}
