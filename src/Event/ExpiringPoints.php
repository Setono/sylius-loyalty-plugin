<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;

/**
 * Dispatched before a lot's expiration entry is written. The points equal the replay-derived
 * remaining of the lot and are not adjustable (ledger invariant). Cancelling defers the lot:
 * no entry is written and the lot is re-selected on the next expiry run — the hook for
 * project-level grace logic.
 */
final class ExpiringPoints
{
    use CancellableTrait;

    public function __construct(
        private readonly LoyaltyAccountInterface $account,
        private readonly CreditLoyaltyTransactionInterface $lot,
        private readonly int $points,
    ) {
    }

    public function getAccount(): LoyaltyAccountInterface
    {
        return $this->account;
    }

    public function getLot(): CreditLoyaltyTransactionInterface
    {
        return $this->lot;
    }

    public function getPoints(): int
    {
        return $this->points;
    }
}
