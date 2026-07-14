<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Earning;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

/**
 * Resolves which channel(s) an action trigger without a shop-channel context (review approval,
 * birthday) should award on. An extension point: tag a replacement to change the strategy.
 */
interface TriggerChannelResolverInterface
{
    /**
     * @return iterable<ChannelInterface>
     */
    public function resolve(CustomerInterface $customer): iterable;
}
