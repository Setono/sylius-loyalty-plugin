<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EventListener;

use Setono\SyliusLoyaltyPlugin\Message\ClaimPastOrderPoints;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

/**
 * On registration, claims points for the customer's past guest orders in the current channel
 * — gated by the program's retroactiveGuestPoints setting (default off).
 */
final class DispatchClaimPastOrderPointsListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly ChannelContextInterface $channelContext,
    ) {
    }

    public function __invoke(GenericEvent $event): void
    {
        $customer = $event->getSubject();
        if (!$customer instanceof CustomerInterface || null === $customer->getId()) {
            return;
        }

        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
            return;
        }

        if (null === $channel->getId() || !$this->programProvider->getByChannel($channel)->isRetroactiveGuestPoints()) {
            return;
        }

        $this->messageBus->dispatch(new Envelope(
            new ClaimPastOrderPoints((int) $customer->getId(), (int) $channel->getId()),
            [new DispatchAfterCurrentBusStamp()],
        ));
    }
}
