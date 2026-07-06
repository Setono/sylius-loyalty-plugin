<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\OrderInterface;

class ClawbackLoyaltyTransaction extends DebitLoyaltyTransaction implements ClawbackLoyaltyTransactionInterface
{
    protected ?OrderInterface $order = null;

    protected ?CreditLoyaltyTransactionInterface $earn = null;

    public function getOrder(): ?OrderInterface
    {
        return $this->order;
    }

    public function setOrder(?OrderInterface $order): void
    {
        $this->order = $order;
    }

    public function getEarn(): ?CreditLoyaltyTransactionInterface
    {
        return $this->earn;
    }

    public function setEarn(?CreditLoyaltyTransactionInterface $earn): void
    {
        $this->earn = $earn;
    }
}
