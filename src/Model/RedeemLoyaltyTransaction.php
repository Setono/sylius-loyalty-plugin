<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\OrderInterface;

class RedeemLoyaltyTransaction extends DebitLoyaltyTransaction implements RedeemLoyaltyTransactionInterface
{
    protected ?OrderInterface $order = null;

    public static function getType(): string
    {
        return 'redeem';
    }

    public function getOrder(): ?OrderInterface
    {
        return $this->order;
    }

    public function setOrder(?OrderInterface $order): void
    {
        $this->order = $order;
    }
}
