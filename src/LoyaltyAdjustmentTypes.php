<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin;

final class LoyaltyAdjustmentTypes
{
    /**
     * The (negative) adjustment representing points redeemed on an order, distributed across
     * the order items' units following Sylius' order-promotion pattern.
     */
    public const REDEMPTION = 'setono_sylius_loyalty_redemption';

    private function __construct()
    {
    }
}
