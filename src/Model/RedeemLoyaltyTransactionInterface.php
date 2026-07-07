<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\OrderInterface;

/**
 * Points spent on an order, written synchronously when the order completes checkout. At most
 * one per (account, order) — enforced by a database unique constraint.
 */
interface RedeemLoyaltyTransactionInterface extends DebitLoyaltyTransactionInterface
{
    public function getOrder(): ?OrderInterface;

    public function setOrder(?OrderInterface $order): void;
}
