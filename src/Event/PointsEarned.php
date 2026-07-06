<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransactionInterface;

/**
 * Dispatched after an earn credit has been committed. Mutations have no effect.
 */
final class PointsEarned
{
    public function __construct(
        public readonly CreditLoyaltyTransactionInterface $transaction,
    ) {
    }
}
