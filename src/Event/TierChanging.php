<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\TierInterface;

/**
 * Dispatched before an account's tier changes. Cancelling keeps the current tier.
 */
final class TierChanging
{
    use CancellableTrait;

    public function __construct(
        public readonly LoyaltyAccountInterface $account,
        public readonly ?TierInterface $from,
        public readonly ?TierInterface $to,
    ) {
    }
}
