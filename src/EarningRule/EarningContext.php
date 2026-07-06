<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * Everything a rule evaluation may look at. Order earning fills the order and the per-item
 * eligible amounts; action-trigger earning fills the trigger context variables instead.
 */
final class EarningContext
{
    /**
     * @param array<int, int> $itemAmounts order item id => eligible amount in minor units of
     *        the channel base currency (discounted, redemption excluded, per the program's
     *        earning basis)
     * @param array<string, mixed> $context typed context variables of the trigger event
     */
    public function __construct(
        public readonly ChannelInterface $channel,
        public readonly ?CustomerInterface $customer = null,
        public readonly ?LoyaltyAccountInterface $account = null,
        public readonly ?OrderInterface $order = null,
        public readonly array $itemAmounts = [],
        public readonly array $context = [],
        private readonly ?\DateTimeImmutable $now = null,
    ) {
    }

    /**
     * The evaluation time — overridable so the rule tester can preview scheduled rules.
     */
    public function getNow(): \DateTimeImmutable
    {
        return $this->now ?? new \DateTimeImmutable();
    }

    /**
     * The total eligible basis in minor units.
     */
    public function getBasis(): int
    {
        return (int) array_sum($this->itemAmounts);
    }
}
