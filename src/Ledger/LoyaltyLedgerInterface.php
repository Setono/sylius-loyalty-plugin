<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Ledger;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
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
}
