<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Basis;

/**
 * The eligible earning basis of an order, split per item so scoped rules can claim items
 * exclusively. The extra amount covers non-item components (shipping under the order_total
 * basis) and only ever feeds order-scoped rules.
 */
final class EligibleBasis
{
    /**
     * @param array<int, int> $itemAmounts order item id => eligible amount in minor units
     */
    public function __construct(
        public readonly array $itemAmounts,
        public readonly int $extraAmount = 0,
    ) {
    }

    public function getTotal(): int
    {
        return (int) array_sum($this->itemAmounts) + $this->extraAmount;
    }
}
