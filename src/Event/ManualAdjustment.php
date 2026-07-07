<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

use Setono\SyliusLoyaltyPlugin\Model\ManualLoyaltyTransactionInterface;

/**
 * Dispatched after a manual credit or debit has been committed. There is deliberately no
 * pre-event: the admin form is the control point.
 */
final class ManualAdjustment
{
    public function __construct(
        public readonly ManualLoyaltyTransactionInterface $transaction,
    ) {
    }
}
