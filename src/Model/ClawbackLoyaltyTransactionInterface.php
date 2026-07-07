<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\OrderInterface;

interface ClawbackLoyaltyTransactionInterface extends DebitLoyaltyTransactionInterface
{
    public function getOrder(): ?OrderInterface;

    public function setOrder(?OrderInterface $order): void;

    public function getEarn(): ?EarnOrderLoyaltyTransactionInterface;

    public function setEarn(?EarnOrderLoyaltyTransactionInterface $earn): void;
}
