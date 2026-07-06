<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

interface LoyaltyAccountProviderInterface
{
    /**
     * Returns the customer's loyalty account in the given channel, creating it lazily on
     * first access.
     */
    public function getByCustomerAndChannel(CustomerInterface $customer, ChannelInterface $channel): LoyaltyAccountInterface;
}
