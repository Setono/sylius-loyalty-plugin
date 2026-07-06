<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Ledger;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;

/**
 * Derives per-lot remainders by deterministically replaying a single account's ledger. This is
 * the only source of lot state — nothing is stored.
 */
interface LotReplayerInterface
{
    /**
     * @param iterable<LoyaltyTransactionInterface> $transactions the account's ledger; it is
     *        re-sorted into replay order (occurredAt ASC, id ASC) internally
     */
    public function replay(iterable $transactions): ReplayResult;
}
