<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

use Setono\SyliusLoyaltyPlugin\Model\RedeemRollbackLoyaltyTransactionInterface;

/**
 * Dispatched after a redemption has been rolled back and committed (the points returned to the
 * account because the order was cancelled). Immutable notification.
 */
final class RedemptionRolledBack
{
    public function __construct(
        public readonly RedeemRollbackLoyaltyTransactionInterface $transaction,
    ) {
    }
}
