<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Repository;

use Setono\SyliusLoyaltyPlugin\Model\TierInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<TierInterface>
 */
interface TierRepositoryInterface extends RepositoryInterface
{
    /**
     * The channel's enabled tiers, highest position first — evaluation order.
     *
     * @return list<TierInterface>
     */
    public function findQualifiable(ChannelInterface $channel): array;
}
