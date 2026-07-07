<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

interface LoyaltyAccountProviderInterface
{
    /**
     * Returns the loyalty account for the given customer and channel, creating it on first access.
     */
    public function getAccount(CustomerInterface $customer, ChannelInterface $channel): LoyaltyAccountInterface;
}
