<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tier;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;

/**
 * Re-evaluates an account's tier. Called by the ledger inside the same transaction after every
 * qualifying earn, so upgrades are immediate.
 */
interface TierEvaluatorInterface
{
    public function evaluate(LoyaltyAccountInterface $account): void;
}
