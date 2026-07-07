<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Referral;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;

interface ReferralCodeGeneratorInterface
{
    /**
     * The account's referral code, generated and persisted on first use.
     */
    public function getCode(LoyaltyAccountInterface $account): string;
}
