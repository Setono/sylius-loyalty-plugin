<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\OrderInterface;

class EarnOrderLoyaltyTransaction extends CreditLoyaltyTransaction implements EarnOrderLoyaltyTransactionInterface
{
    use RulesBreakdownAwareTrait;

    protected ?OrderInterface $order = null;

    protected ?int $basisAmount = null;

    public function getOrder(): ?OrderInterface
    {
        return $this->order;
    }

    public function setOrder(?OrderInterface $order): void
    {
        $this->order = $order;
    }

    public function getBasisAmount(): ?int
    {
        return $this->basisAmount;
    }

    public function setBasisAmount(?int $basisAmount): void
    {
        $this->basisAmount = $basisAmount;
    }
}
