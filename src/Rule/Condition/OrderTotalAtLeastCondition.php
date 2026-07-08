<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule\Condition;

use Setono\SyliusLoyaltyPlugin\Rule\RuleEvaluationContext;

final class OrderTotalAtLeastCondition implements EarningConditionInterface
{
    public function getType(): string
    {
        return 'order_total_at_least';
    }

    public function isSatisfied(RuleEvaluationContext $context, array $configuration): bool
    {
        $order = $context->getOrder();
        if (null === $order) {
            return false;
        }

        $amount = $configuration['amount'] ?? 0;

        return $order->getTotal() >= (is_numeric($amount) ? (int) $amount : 0);
    }
}
