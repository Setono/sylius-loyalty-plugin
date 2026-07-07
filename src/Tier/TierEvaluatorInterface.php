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
    /**
     * Upgrade-only: called inline after qualifying earns.
     */
    public function evaluate(LoyaltyAccountInterface $account): void;

    /**
     * Full evaluation including downgrades with the program's grace period — the nightly
     * evaluate-tiers pass.
     */
    public function reconcile(LoyaltyAccountInterface $account, \DateTimeImmutable $now): void;
}
