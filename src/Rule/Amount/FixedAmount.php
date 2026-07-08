<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule\Amount;

/**
 * A flat number of points. Order-scoped it awards `points` once (quantity 1); item-scoped it awards
 * `points` per matching unit. A rate of 0 is the standard way to make a product earn nothing.
 */
final class FixedAmount implements EarningAmountInterface
{
    public static function getType(): string
    {
        return 'fixed';
    }

    public function calculate(EarningAmountContext $context, array $configuration): int
    {
        $points = $configuration['points'] ?? 0;
        $points = is_numeric($points) ? (int) $points : 0;

        return max(0, $points * $context->quantity);
    }
}
