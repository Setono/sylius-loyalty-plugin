<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\OrderInterface;

interface RedeemLoyaltyTransactionInterface extends DebitLoyaltyTransactionInterface
{
    public function getOrder(): ?OrderInterface;

    public function setOrder(?OrderInterface $order): void;
}
