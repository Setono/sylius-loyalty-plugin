<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;

/**
 * Dispatched before points are awarded. Listeners may adjust the points or the expiry, or set
 * $cancelled to true to skip the award entirely (no ledger entry is written).
 */
final class AwardingPoints
{
    public function __construct(
        public readonly LoyaltyAccountInterface $account,
        public int $points,
        public ?\DateTimeInterface $expiresAt = null,
        public bool $cancelled = false,
    ) {
    }
}
