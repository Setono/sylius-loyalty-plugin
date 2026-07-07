<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

interface ExpireLoyaltyTransactionInterface extends DebitLoyaltyTransactionInterface
{
    public function getLot(): ?CreditLoyaltyTransactionInterface;

    public function setLot(?CreditLoyaltyTransactionInterface $lot): void;
}
