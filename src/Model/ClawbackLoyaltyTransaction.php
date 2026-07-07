<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\OrderInterface;

class ClawbackLoyaltyTransaction extends DebitLoyaltyTransaction implements ClawbackLoyaltyTransactionInterface
{
    protected ?OrderInterface $order = null;

    protected ?EarnOrderLoyaltyTransactionInterface $earn = null;

    public static function getType(): string
    {
        return 'clawback';
    }

    public function getOrder(): ?OrderInterface
    {
        return $this->order;
    }

    public function setOrder(?OrderInterface $order): void
    {
        $this->order = $order;
    }

    public function getEarn(): ?EarnOrderLoyaltyTransactionInterface
    {
        return $this->earn;
    }

    public function setEarn(?EarnOrderLoyaltyTransactionInterface $earn): void
    {
        $this->earn = $earn;
    }
}
