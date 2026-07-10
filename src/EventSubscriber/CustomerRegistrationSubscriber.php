<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EventSubscriber;

use Setono\SyliusLoyaltyPlugin\Earning\ActionPointsAwarderInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Awards the "customer registered" earning rules (a signup bonus) when a customer registers, on the
 * channel they registered on.
 */
final class CustomerRegistrationSubscriber implements EventSubscriberInterface
{
    public const TRIGGER = 'customer_registered';

    public function __construct(
        private readonly ActionPointsAwarderInterface $awarder,
        private readonly ChannelContextInterface $channelContext,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.customer.post_register' => 'award',
        ];
    }

    public function award(GenericEvent $event): void
    {
        $customer = $event->getSubject();
        if (!$customer instanceof CustomerInterface) {
            return;
        }

        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
            return;
        }

        if (!$channel instanceof ChannelInterface) {
            return;
        }

        $this->awarder->award($customer, $channel, self::TRIGGER, sprintf('%s:%d', self::TRIGGER, (int) $customer->getId()));
    }
}
