<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\TierInterface;

/**
 * Dispatched after an account's tier changed — the hook for tier communications (no email
 * ships with the plugin).
 */
final class TierChanged
{
    public function __construct(
        public readonly LoyaltyAccountInterface $account,
        public readonly ?TierInterface $from,
        public readonly ?TierInterface $to,
    ) {
    }
}
