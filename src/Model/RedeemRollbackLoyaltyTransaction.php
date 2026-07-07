<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

class RedeemRollbackLoyaltyTransaction extends CreditLoyaltyTransaction implements RedeemRollbackLoyaltyTransactionInterface
{
    protected ?RedeemLoyaltyTransactionInterface $redeem = null;

    public function getRedeem(): ?RedeemLoyaltyTransactionInterface
    {
        return $this->redeem;
    }

    public function setRedeem(?RedeemLoyaltyTransactionInterface $redeem): void
    {
        $this->redeem = $redeem;
    }

    public static function getDiscriminator(): string
    {
        return 'redeem_rollback';
    }
}
