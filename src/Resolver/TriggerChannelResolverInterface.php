<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Resolver;

use Setono\SyliusLoyaltyPlugin\Event\Trigger\EarningTriggerEvent;
use Sylius\Component\Channel\Model\ChannelInterface;

/**
 * Resolves the channel of a trigger event when it carries none. Re-alias this service to
 * change the resolution strategy.
 */
interface TriggerChannelResolverInterface
{
    public function resolve(EarningTriggerEvent $event): ?ChannelInterface;
}
