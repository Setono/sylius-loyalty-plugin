<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule\Amount;

/**
 * The input an earning amount is computed against: the eligible basis (in minor units) the rule earns
 * over, and the number of matching units it applies to (1 for an order-scoped rule; the matching item
 * quantity for a taxon/product-scoped rule).
 */
final class EarningAmountContext
{
    public function __construct(
        public readonly int $basis,
        public readonly int $quantity = 1,
    ) {
    }
}
