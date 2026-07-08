<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * Dispatched before points are redeemed on an order at checkout. $points is the amount about to be
 * debited (already clamped to the available balance). Listeners may adjust it, or set $cancelled to true
 * to skip the redemption entirely (no ledger entry is written).
 */
final class RedeemingPoints
{
    public function __construct(
        public readonly LoyaltyAccountInterface $account,
        public readonly OrderInterface $order,
        public int $points,
        public bool $cancelled = false,
    ) {
    }
}
