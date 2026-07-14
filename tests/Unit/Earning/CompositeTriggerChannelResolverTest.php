<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Earning;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Earning\CompositeTriggerChannelResolver;
use Setono\SyliusLoyaltyPlugin\Earning\TriggerChannelResolverInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

final class CompositeTriggerChannelResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_merges_the_channels_of_every_resolver_de_duplicated_by_code(): void
    {
        $customer = $this->prophesize(CustomerInterface::class)->reveal();
        $web = $this->channel('web');
        $mobile = $this->channel('mobile');

        $first = $this->prophesize(TriggerChannelResolverInterface::class);
        $first->resolve($customer)->willReturn([$web, $mobile]);

        $second = $this->prophesize(TriggerChannelResolverInterface::class);
        $second->resolve($customer)->willReturn([$this->channel('web')]);

        $composite = new CompositeTriggerChannelResolver();
        $composite->add($first->reveal());
        $composite->add($second->reveal());

        $codes = [];
        foreach ($composite->resolve($customer) as $channel) {
            $codes[] = $channel->getCode();
        }

        self::assertSame(['web', 'mobile'], $codes);
    }

    private function channel(string $code): ChannelInterface
    {
        $channel = $this->prophesize(ChannelInterface::class);
        $channel->getCode()->willReturn($code);

        return $channel->reveal();
    }
}
