<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule;

use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;

/**
 * How a single rule fared during an evaluation — the payload behind the rule tester, the
 * dry-run audit list, and the ledger's rules breakdown.
 */
final class RuleEvaluation
{
    /**
     * @param list<string> $failedConditions condition types that did not pass
     * @param array<int, int> $claimedItems order item id => claimed basis amount (minor units)
     * @param float $points the rule's (unrounded) contribution; 0 for multiplier rules
     * @param float|null $factor the multiplier factor; null for base rules
     * @param bool $applied whether the rule survived the stacking resolution
     */
    public function __construct(
        public readonly EarningRuleInterface $rule,
        public readonly bool $matched,
        public readonly array $failedConditions,
        public readonly array $claimedItems,
        public readonly int $claimedBasis,
        public readonly int $claimedUnits,
        public readonly float $points,
        public readonly ?float $factor,
        public readonly bool $applied,
    ) {
    }
}
