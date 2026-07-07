<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Ledger;

use Setono\SyliusLoyaltyPlugin\Model\DebitLoyaltyTransactionInterface;

/**
 * A replay-derived consumption: the given debit consumed the given number of points from a lot.
 */
final class Consumption
{
    public function __construct(
        public readonly DebitLoyaltyTransactionInterface $debit,
        public readonly int $points,
    ) {
    }
}
