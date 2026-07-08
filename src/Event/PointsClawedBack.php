<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

use Setono\SyliusLoyaltyPlugin\Model\ClawbackLoyaltyTransactionInterface;

/**
 * Dispatched after points have been clawed back and committed. Immutable notification; mutating the
 * transaction here has no effect on what was written.
 */
final class PointsClawedBack
{
    public function __construct(
        public readonly ClawbackLoyaltyTransactionInterface $transaction,
    ) {
    }
}
