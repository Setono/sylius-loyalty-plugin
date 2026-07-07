<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Rule;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Rule\RuleEvaluationContext;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class RuleEvaluationContextTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_exposes_the_channel_order_customer_and_evaluated_at(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $order = $this->prophesize(OrderInterface::class)->reveal();
        $customer = $this->prophesize(CustomerInterface::class)->reveal();
        $evaluatedAt = new \DateTimeImmutable('2026-01-01 10:00:00');

        $context = new RuleEvaluationContext($channel, $evaluatedAt, $order, $customer);

        self::assertSame($channel, $context->getChannel());
        self::assertSame($evaluatedAt, $context->getEvaluatedAt());
        self::assertSame($order, $context->getOrder());
        self::assertSame($customer, $context->getCustomer());
    }

    /**
     * @test
     */
    public function for_order_derives_the_channel_and_customer_from_the_order(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $customer = $this->prophesize(CustomerInterface::class)->reveal();
        $evaluatedAt = new \DateTimeImmutable('2026-11-27 00:30:00');

        $order = $this->prophesize(OrderInterface::class);
        $order->getChannel()->willReturn($channel);
        $order->getCustomer()->willReturn($customer);

        $context = RuleEvaluationContext::forOrder($order->reveal(), $evaluatedAt);

        self::assertSame($channel, $context->getChannel());
        self::assertSame($customer, $context->getCustomer());
        self::assertSame($evaluatedAt, $context->getEvaluatedAt());
        self::assertSame($order->reveal(), $context->getOrder());
    }

    /**
     * @test
     */
    public function for_order_requires_a_channel(): void
    {
        $order = $this->prophesize(OrderInterface::class);
        $order->getChannel()->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);

        RuleEvaluationContext::forOrder($order->reveal(), new \DateTimeImmutable('2026-01-01'));
    }
}
