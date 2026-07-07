<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

class ExpireLoyaltyTransaction extends DebitLoyaltyTransaction implements ExpireLoyaltyTransactionInterface
{
    protected ?CreditLoyaltyTransactionInterface $lot = null;

    public static function getType(): string
    {
        return 'expire';
    }

    public function getLot(): ?CreditLoyaltyTransactionInterface
    {
        return $this->lot;
    }

    public function setLot(?CreditLoyaltyTransactionInterface $lot): void
    {
        $this->lot = $lot;
    }
}
