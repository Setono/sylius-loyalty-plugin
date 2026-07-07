<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * The input an earning rule is evaluated against: the channel, the order (for order-triggered rules),
 * the customer, and the moment evaluation happens (which the admin rule tester can override to preview
 * scheduled rules before their window opens).
 */
final class RuleEvaluationContext
{
    public function __construct(
        private readonly ChannelInterface $channel,
        private readonly \DateTimeInterface $evaluatedAt,
        private readonly ?OrderInterface $order = null,
        private readonly ?CustomerInterface $customer = null,
    ) {
    }

    public static function forOrder(OrderInterface $order, \DateTimeInterface $evaluatedAt): self
    {
        $channel = $order->getChannel();
        if (null === $channel) {
            throw new \InvalidArgumentException('The order must belong to a channel to be evaluated.');
        }

        $customer = $order->getCustomer();

        return new self($channel, $evaluatedAt, $order, $customer instanceof CustomerInterface ? $customer : null);
    }

    public function getChannel(): ChannelInterface
    {
        return $this->channel;
    }

    public function getEvaluatedAt(): \DateTimeInterface
    {
        return $this->evaluatedAt;
    }

    public function getOrder(): ?OrderInterface
    {
        return $this->order;
    }

    public function getCustomer(): ?CustomerInterface
    {
        return $this->customer;
    }
}
