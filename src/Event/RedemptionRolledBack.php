<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

use Setono\SyliusLoyaltyPlugin\Model\RedeemRollbackLoyaltyTransactionInterface;

/**
 * Dispatched after a redemption rollback credit has been committed. There is deliberately no
 * pre-event: the rollback must restore exactly the rolled-back points.
 */
final class RedemptionRolledBack
{
    public function __construct(
        public readonly RedeemRollbackLoyaltyTransactionInterface $transaction,
    ) {
    }
}
