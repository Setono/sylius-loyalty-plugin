<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Sylius\Component\Core\Model\ChannelInterface;

interface LoyaltyProgramProviderInterface
{
    /**
     * Returns the loyalty program for the given channel, creating it with defaults on first access.
     */
    public function getProgram(ChannelInterface $channel): LoyaltyProgramInterface;
}
