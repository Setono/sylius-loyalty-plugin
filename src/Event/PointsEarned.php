<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransactionInterface;

/**
 * Dispatched after points have been awarded and committed. Immutable notification; mutating the
 * transaction here has no effect on what was written.
 */
final class PointsEarned
{
    public function __construct(
        public readonly CreditLoyaltyTransactionInterface $transaction,
    ) {
    }
}
