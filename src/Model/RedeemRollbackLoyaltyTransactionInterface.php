<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

interface RedeemRollbackLoyaltyTransactionInterface extends CreditLoyaltyTransactionInterface
{
    public function getRedeem(): ?RedeemLoyaltyTransactionInterface;

    public function setRedeem(?RedeemLoyaltyTransactionInterface $redeem): void;
}
