<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransactionInterface;

/**
 * Dispatched after the redemption debit has been committed. Mutations have no effect.
 */
final class PointsRedeemed
{
    public function __construct(
        public readonly RedeemLoyaltyTransactionInterface $transaction,
    ) {
    }
}
