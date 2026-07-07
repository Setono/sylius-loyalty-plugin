<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Message;

/**
 * Awards the points for an order per the enabled earning rules. Carries the order id — never
 * the entity — and is fully idempotent, so sync and async transports (and redeliveries) are
 * equally safe.
 */
final class AwardOrderPoints
{
    public function __construct(
        public readonly int $orderId,
    ) {
    }
}
