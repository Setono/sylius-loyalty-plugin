<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;

/**
 * Dispatched before points are clawed back for a cancelled or refunded order. $points is the amount
 * about to be debited (already clamped per the program's clawback policy). Listeners may adjust it, or
 * set $cancelled to true to skip the clawback entirely (no ledger entry is written).
 */
final class ClawingBackPoints
{
    public function __construct(
        public readonly LoyaltyAccountInterface $account,
        public readonly EarnOrderLoyaltyTransactionInterface $earn,
        public int $points,
        public bool $cancelled = false,
    ) {
    }
}
