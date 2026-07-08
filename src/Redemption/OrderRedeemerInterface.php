<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Redemption;

use Sylius\Component\Core\Model\OrderInterface;

interface OrderRedeemerInterface
{
    /**
     * Debits the points an order redeems, at checkout completion. The amount is read from the redemption
     * adjustment the order processor wrote, so it matches exactly what the customer was shown. A no-op
     * for guest orders and orders with no redemption; delegates to the idempotent ledger.
     */
    public function redeem(OrderInterface $order): void;

    /**
     * Returns the redeemed points to the account, when the order is cancelled.
     */
    public function rollback(OrderInterface $order): void;
}
