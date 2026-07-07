<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Ledger;

use Setono\SyliusLoyaltyPlugin\Model\ExpireLoyaltyTransactionInterface;

/**
 * The replay-derived view of an expiration entry: what the referenced lot's remaining was at
 * the moment the entry was applied. Ledger invariant 3 requires the entry's points to equal
 * this remaining.
 */
final class ExpirationState
{
    public function __construct(
        public readonly ExpireLoyaltyTransactionInterface $expiration,
        public readonly int $remainingBefore,
    ) {
    }
}
