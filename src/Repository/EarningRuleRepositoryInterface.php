<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Repository;

use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<EarningRuleInterface>
 */
interface EarningRuleRepositoryInterface extends RepositoryInterface
{
    /**
     * The enabled rules for a channel and trigger, highest priority first. The time window and
     * conditions are evaluated later (they depend on the evaluation moment, e.g. the rule tester's
     * date override), so they are not filtered here.
     *
     * @return list<EarningRuleInterface>
     */
    public function findEnabledByChannelAndTrigger(ChannelInterface $channel, string $trigger): array;
}
