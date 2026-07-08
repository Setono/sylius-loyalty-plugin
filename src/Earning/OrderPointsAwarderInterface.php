<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Earning;

use Sylius\Component\Core\Model\OrderInterface;

interface OrderPointsAwarderInterface
{
    /**
     * Awards the points an order has earned: loads the customer's account for the channel, evaluates the
     * channel's rules, and writes the earn through the ledger. A no-op for guest orders (they earn on
     * registration instead), disabled accounts, and zero-point results. Idempotent — the ledger's
     * unique constraint makes a re-award for the same order a no-op.
     */
    public function award(OrderInterface $order, ?\DateTimeInterface $awardedAt = null): void;
}
