<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

use Setono\SyliusLoyaltyPlugin\Model\ClawbackLoyaltyTransactionInterface;

/**
 * Dispatched after a clawback debit has been committed. Mutations have no effect.
 */
final class PointsClawedBack
{
    public function __construct(
        public readonly ClawbackLoyaltyTransactionInterface $transaction,
    ) {
    }
}
