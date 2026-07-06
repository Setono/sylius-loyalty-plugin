<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Repository;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\ReferralInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<ReferralInterface>
 */
interface ReferralRepositoryInterface extends RepositoryInterface
{
    public function findOneByRefereeAndChannel(CustomerInterface $referee, ChannelInterface $channel): ?ReferralInterface;

    /**
     * How many referrals the account was rewarded for since the given time — the reward-cap
     * fraud check.
     */
    public function countRewardedSince(LoyaltyAccountInterface $referrerAccount, \DateTimeImmutable $since): int;

    /**
     * @return list<ReferralInterface>
     */
    public function findPendingOlderThan(\DateTimeImmutable $threshold, int $limit): array;

    /**
     * @return list<ReferralInterface>
     */
    public function findWithIpHashOlderThan(\DateTimeImmutable $threshold, int $limit): array;

    /**
     * Aggregate stats for the shop referral block.
     *
     * @return array{rewarded: int, pointsEarned: int}
     */
    public function getReferrerStats(LoyaltyAccountInterface $referrerAccount): array;
}
