<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

use Setono\SyliusLoyaltyPlugin\Model\ExpireLoyaltyTransactionInterface;

/**
 * Dispatched after a lot's expiration entry has been committed. Mutations have no effect.
 */
final class PointsExpired
{
    public function __construct(
        public readonly ExpireLoyaltyTransactionInterface $transaction,
    ) {
    }
}
