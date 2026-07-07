<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

interface LoyaltyProgramProviderInterface
{
    /**
     * Returns the channel's loyalty program, creating it with defaults on first access.
     */
    public function getByChannel(ChannelInterface $channel): LoyaltyProgramInterface;
}
