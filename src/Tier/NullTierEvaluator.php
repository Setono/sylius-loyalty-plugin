<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tier;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;

/**
 * Tiers ship in Phase 2; until then the ledger's tier evaluation seam does nothing. The real
 * evaluator replaces this by re-aliasing TierEvaluatorInterface.
 */
final class NullTierEvaluator implements TierEvaluatorInterface
{
    public function evaluate(LoyaltyAccountInterface $account): void
    {
    }
}
