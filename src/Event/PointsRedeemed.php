<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransactionInterface;

/**
 * Dispatched after points have been redeemed and committed. Immutable notification.
 */
final class PointsRedeemed
{
    public function __construct(
        public readonly RedeemLoyaltyTransactionInterface $transaction,
    ) {
    }
}
