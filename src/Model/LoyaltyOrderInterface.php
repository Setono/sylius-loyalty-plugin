<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\OrderInterface;

interface LoyaltyOrderInterface extends OrderInterface
{
    /**
     * The number of loyalty points the customer has asked to spend on this order. This is an intent: the
     * amount actually debited is clamped to what the customer can afford and the program allows, on every
     * order recalculation. Apply this contract to the application's order with the LoyaltyOrderTrait.
     */
    public function getLoyaltyPointsRequested(): int;

    public function setLoyaltyPointsRequested(int $loyaltyPointsRequested): void;
}
