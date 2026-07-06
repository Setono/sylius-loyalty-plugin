<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

// todo: Usually I extend the OrderInterface in Sylius core
/**
 * Implemented by the host project's Order entity (together with LoyaltyOrderTrait) to enable
 * point redemption at checkout.
 */
interface LoyaltyOrderInterface
{
    /**
     * The customer's persisted intent: "spend N points on this order". The applied amount is
     * derived from it on every order recalculation — clamped to the balance and the program's
     * cap — and the stored request is never overwritten by clamping.
     */
    public function getLoyaltyPointsRequested(): ?int;

    public function setLoyaltyPointsRequested(?int $loyaltyPointsRequested): void;
}
