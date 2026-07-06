<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

/**
 * Restores the points of a rolled-back redemption (e.g. a cancelled unpaid order) as a new
 * lot. Not counted toward lifetimeEarned — the restored points were already counted when
 * originally earned.
 */
interface RedeemRollbackLoyaltyTransactionInterface extends CreditLoyaltyTransactionInterface
{
    public function getRedeem(): ?RedeemLoyaltyTransactionInterface;

    public function setRedeem(?RedeemLoyaltyTransactionInterface $redeem): void;
}
