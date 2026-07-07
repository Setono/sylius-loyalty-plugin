<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

interface EarnReferralLoyaltyTransactionInterface extends CreditLoyaltyTransactionInterface
{
    public function getReferral(): ?ReferralInterface;

    public function setReferral(?ReferralInterface $referral): void;
}
