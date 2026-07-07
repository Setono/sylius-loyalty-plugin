<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Referral\FraudCheck;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\ReferralInterface;
use Setono\SyliusLoyaltyPlugin\Repository\ReferralRepositoryInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * Flags referrers over the rewarded-referrals cap for the trailing 30 days (configurable,
 * default 10).
 */
final class RewardCapCheck implements ReferralFraudCheckInterface
{
    public function __construct(
        private readonly ReferralRepositoryInterface $referralRepository,
        private readonly int $cap,
    ) {
    }

    public function check(ReferralInterface $referral, OrderInterface $order): ?FraudFlag
    {
        $referrerAccount = $referral->getReferrerAccount();
        if (!$referrerAccount instanceof LoyaltyAccountInterface) {
            return null;
        }

        $rewarded = $this->referralRepository->countRewardedSince($referrerAccount, new \DateTimeImmutable('-30 days'));
        if ($rewarded >= $this->cap) {
            return new FraudFlag('reward_cap', sprintf('The referrer already has %d rewarded referrals in 30 days (cap %d)', $rewarded, $this->cap));
        }

        return null;
    }
}
