<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Earning\ActionPointsAwarderInterface;
use Setono\SyliusLoyaltyPlugin\EventSubscriber\CustomerRegistrationSubscriber;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class CustomerRegistrationSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_subscribes_to_the_post_register_event(): void
    {
        self::assertArrayHasKey('sylius.customer.post_register', CustomerRegistrationSubscriber::getSubscribedEvents());
    }

    /**
     * @test
     */
    public function it_awards_the_signup_bonus_on_the_current_channel(): void
    {
        $customer = $this->prophesize(CustomerInterface::class);
        $customer->getId()->willReturn(42);
        $channel = $this->prophesize(ChannelInterface::class)->reveal();

        $channelContext = $this->prophesize(ChannelContextInterface::class);
        $channelContext->getChannel()->willReturn($channel);

        $awarder = $this->prophesize(ActionPointsAwarderInterface::class);
        $awarder->award($customer->reveal(), $channel, 'customer_registered', 'customer_registered:42')->shouldBeCalled();

        $subscriber = new CustomerRegistrationSubscriber($awarder->reveal(), $channelContext->reveal());
        $subscriber->award(new GenericEvent($customer->reveal()));
    }

    /**
     * @test
     */
    public function it_does_nothing_when_the_subject_is_not_a_customer(): void
    {
        $awarder = $this->prophesize(ActionPointsAwarderInterface::class);
        $awarder->award(\Prophecy\Argument::cetera())->shouldNotBeCalled();

        $subscriber = new CustomerRegistrationSubscriber(
            $awarder->reveal(),
            $this->prophesize(ChannelContextInterface::class)->reveal(),
        );
        $subscriber->award(new GenericEvent(new \stdClass()));
    }

    /**
     * @test
     */
    public function it_does_nothing_when_the_channel_is_not_a_core_channel(): void
    {
        $customer = $this->prophesize(CustomerInterface::class)->reveal();

        $channelContext = $this->prophesize(ChannelContextInterface::class);
        $channelContext->getChannel()->willReturn($this->prophesize(\Sylius\Component\Channel\Model\ChannelInterface::class)->reveal());

        $awarder = $this->prophesize(ActionPointsAwarderInterface::class);
        $awarder->award(\Prophecy\Argument::cetera())->shouldNotBeCalled();

        $subscriber = new CustomerRegistrationSubscriber($awarder->reveal(), $channelContext->reveal());
        $subscriber->award(new GenericEvent($customer));
    }

    /**
     * @test
     */
    public function it_does_nothing_when_no_channel_is_available(): void
    {
        $customer = $this->prophesize(CustomerInterface::class)->reveal();

        $channelContext = $this->prophesize(ChannelContextInterface::class);
        $channelContext->getChannel()->willThrow(new ChannelNotFoundException());

        $awarder = $this->prophesize(ActionPointsAwarderInterface::class);
        $awarder->award(\Prophecy\Argument::cetera())->shouldNotBeCalled();

        $subscriber = new CustomerRegistrationSubscriber($awarder->reveal(), $channelContext->reveal());
        $subscriber->award(new GenericEvent($customer));
    }
}
