<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Earning\Trigger;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Earning\Trigger\AwardOrderPointsTrigger;
use Setono\SyliusLoyaltyPlugin\Message\Command\AwardOrderPoints;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class AwardOrderPointsTriggerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_dispatches_the_command_when_the_moment_is_the_programs_award_moment(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);

        $order = $this->prophesize(OrderInterface::class);
        $order->getChannel()->willReturn($channel->reveal());
        $order->getId()->willReturn(42);

        $program = $this->prophesize(LoyaltyProgramInterface::class);
        $program->getAwardOrderPointsAt()->willReturn(LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_PAYMENT_PAID);

        $programProvider = $this->prophesize(LoyaltyProgramProviderInterface::class);
        $programProvider->getProgram($channel->reveal())->willReturn($program->reveal());

        $commandBus = $this->prophesize(MessageBusInterface::class);
        $commandBus->dispatch(Argument::that(static fn (AwardOrderPoints $command): bool => 42 === $command->order))
            ->willReturn(new Envelope(new \stdClass()))
            ->shouldBeCalled();

        $trigger = new AwardOrderPointsTrigger($programProvider->reveal(), $commandBus->reveal());
        $trigger->trigger($order->reveal(), LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_PAYMENT_PAID);
    }

    /**
     * @test
     */
    public function it_does_not_dispatch_when_the_moment_is_not_the_programs_award_moment(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);

        $order = $this->prophesize(OrderInterface::class);
        $order->getChannel()->willReturn($channel->reveal());

        $program = $this->prophesize(LoyaltyProgramInterface::class);
        $program->getAwardOrderPointsAt()->willReturn(LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_PAYMENT_PAID);

        $programProvider = $this->prophesize(LoyaltyProgramProviderInterface::class);
        $programProvider->getProgram($channel->reveal())->willReturn($program->reveal());

        $commandBus = $this->prophesize(MessageBusInterface::class);
        $commandBus->dispatch(Argument::any())->shouldNotBeCalled();

        $trigger = new AwardOrderPointsTrigger($programProvider->reveal(), $commandBus->reveal());
        $trigger->trigger($order->reveal(), LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_ORDER_FULFILLED);
    }

    /**
     * @test
     */
    public function it_does_nothing_for_an_order_without_a_channel(): void
    {
        $order = $this->prophesize(OrderInterface::class);
        $order->getChannel()->willReturn(null);

        $programProvider = $this->prophesize(LoyaltyProgramProviderInterface::class);
        $programProvider->getProgram(Argument::any())->shouldNotBeCalled();

        $commandBus = $this->prophesize(MessageBusInterface::class);
        $commandBus->dispatch(Argument::any())->shouldNotBeCalled();

        $trigger = new AwardOrderPointsTrigger($programProvider->reveal(), $commandBus->reveal());
        $trigger->trigger($order->reveal(), LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_PAYMENT_PAID);
    }
}
