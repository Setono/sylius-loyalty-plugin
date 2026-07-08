<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule;

/**
 * The outcome of computing an order's earning: the final points to award, the per-rule base
 * contributions (rule id => points — feeding EarnOrderLoyaltyTransaction.rulesBreakdown and the admin
 * rule tester), and the multiplier that was applied to the summed base.
 */
final class EarningResult
{
    /**
     * @param array<int, int> $breakdown rule id => base points contributed
     */
    public function __construct(
        public readonly int $points,
        public readonly array $breakdown = [],
        public readonly float $multiplier = 1.0,
    ) {
    }
}
