<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Message;

/**
 * Awards points retroactively for a freshly registered customer's past guest orders (they
 * already belong to the registering customer — Sylius attaches the new user to the existing
 * customer with the same email). Only dispatched when the program's retroactiveGuestPoints
 * is enabled.
 */
final class ClaimPastOrderPoints
{
    public function __construct(
        public readonly int $customerId,
        public readonly int $channelId,
    ) {
    }
}
