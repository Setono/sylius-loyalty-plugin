<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

class EarnReferralLoyaltyTransaction extends CreditLoyaltyTransaction implements EarnReferralLoyaltyTransactionInterface
{
    protected ?ReferralInterface $referral = null;

    public function getReferral(): ?ReferralInterface
    {
        return $this->referral;
    }

    public function setReferral(?ReferralInterface $referral): void
    {
        $this->referral = $referral;
    }
}
