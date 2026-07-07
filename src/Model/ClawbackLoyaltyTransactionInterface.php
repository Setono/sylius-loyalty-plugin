<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\OrderInterface;

/**
 * Debits the points originally earned for an order that was cancelled or refunded. Each credit
 * is clawed back at most once — enforced by a database unique constraint on the earn reference.
 * The order reference is informational only.
 */
interface ClawbackLoyaltyTransactionInterface extends DebitLoyaltyTransactionInterface
{
    public function getOrder(): ?OrderInterface;

    public function setOrder(?OrderInterface $order): void;

    public function getEarn(): ?CreditLoyaltyTransactionInterface;

    public function setEarn(?CreditLoyaltyTransactionInterface $earn): void;
}
