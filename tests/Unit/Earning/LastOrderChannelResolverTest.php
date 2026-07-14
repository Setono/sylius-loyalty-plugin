<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Earning;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Earning\LastOrderChannelResolver;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;

final class LastOrderChannelResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_resolves_the_channel_of_the_customers_most_recent_order(): void
    {
        $customer = $this->prophesize(CustomerInterface::class)->reveal();
        $channel = $this->prophesize(ChannelInterface::class)->reveal();

        $order = $this->prophesize(OrderInterface::class);
        $order->getChannel()->willReturn($channel);

        $repository = $this->prophesize(OrderRepositoryInterface::class);
        $repository->findBy(['customer' => $customer], ['id' => 'DESC'], 1)->willReturn([$order->reveal()]);

        $channels = [];
        foreach ((new LastOrderChannelResolver($repository->reveal()))->resolve($customer) as $resolved) {
            $channels[] = $resolved;
        }

        self::assertSame([$channel], $channels);
    }

    /**
     * @test
     */
    public function it_resolves_nothing_when_the_customer_has_no_order(): void
    {
        $customer = $this->prophesize(CustomerInterface::class)->reveal();

        $repository = $this->prophesize(OrderRepositoryInterface::class);
        $repository->findBy(['customer' => $customer], ['id' => 'DESC'], 1)->willReturn([]);

        $channels = [];
        foreach ((new LastOrderChannelResolver($repository->reveal()))->resolve($customer) as $resolved) {
            $channels[] = $resolved;
        }

        self::assertCount(0, $channels);
    }
}
