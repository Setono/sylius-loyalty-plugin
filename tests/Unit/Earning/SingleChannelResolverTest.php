<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Earning;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Earning\SingleChannelResolver;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

final class SingleChannelResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_resolves_the_sole_enabled_channel(): void
    {
        $customer = $this->prophesize(CustomerInterface::class)->reveal();
        $channel = $this->prophesize(ChannelInterface::class)->reveal();

        $repository = $this->prophesize(ChannelRepositoryInterface::class);
        $repository->findBy(['enabled' => true])->willReturn([$channel]);

        $channels = [];
        foreach ((new SingleChannelResolver($repository->reveal()))->resolve($customer) as $resolved) {
            $channels[] = $resolved;
        }

        self::assertSame([$channel], $channels);
    }

    /**
     * @test
     */
    public function it_resolves_nothing_when_there_is_more_than_one_channel(): void
    {
        $customer = $this->prophesize(CustomerInterface::class)->reveal();

        $repository = $this->prophesize(ChannelRepositoryInterface::class);
        $repository->findBy(['enabled' => true])->willReturn([
            $this->prophesize(ChannelInterface::class)->reveal(),
            $this->prophesize(ChannelInterface::class)->reveal(),
        ]);

        $channels = [];
        foreach ((new SingleChannelResolver($repository->reveal()))->resolve($customer) as $resolved) {
            $channels[] = $resolved;
        }

        self::assertCount(0, $channels);
    }
}
