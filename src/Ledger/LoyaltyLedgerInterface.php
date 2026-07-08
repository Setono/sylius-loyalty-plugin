<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Ledger;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * The single entry point for ledger writes. Every write locks the account, dispatches its mutable
 * pre-event and immutable post-event, appends the transaction, and recomputes the account's cached
 * balance and lifetime-earned — all inside one transaction. Duplicate deliveries are idempotent
 * no-ops thanks to the ledger's unique constraints (§3.3).
 */
interface LoyaltyLedgerInterface
{
    /**
     * @param array<int, mixed> $rulesBreakdown rule id => points contributed
     */
    public function earnForOrder(
        LoyaltyAccountInterface $account,
        OrderInterface $order,
        int $points,
        int $basisAmount = 0,
        array $rulesBreakdown = [],
        ?\DateTimeInterface $expiresAt = null,
    ): void;

    /**
     * @param array<int, mixed> $rulesBreakdown rule id => points contributed
     */
    public function earnForAction(
        LoyaltyAccountInterface $account,
        string $sourceIdentifier,
        int $points,
        array $rulesBreakdown = [],
        ?\DateTimeInterface $expiresAt = null,
    ): void;

    /**
     * Reverses the points earned for an order that was cancelled or refunded: writes one
     * ClawbackLoyaltyTransaction per earn-for-order entry, each debit clamped per the clawback policy
     * (clamp-to-zero never drives the balance negative; allow-negative reverses the full earn).
     * Idempotent — an earn that already has a clawback is skipped.
     */
    public function clawback(
        OrderInterface $order,
        string $clawbackPolicy = LoyaltyProgramInterface::CLAWBACK_POLICY_CLAMP_TO_ZERO,
    ): void;
}
