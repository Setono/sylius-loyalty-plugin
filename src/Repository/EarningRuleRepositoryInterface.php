<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Repository;

use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<EarningRuleInterface>
 */
interface EarningRuleRepositoryInterface extends RepositoryInterface
{
    /**
     * Returns the channel's enabled rules for the given trigger. The active window is checked
     * by the evaluator (against the possibly overridden evaluation time), not here.
     *
     * @return list<EarningRuleInterface>
     */
    public function findForEvaluation(ChannelInterface $channel, string $trigger): array;
}
