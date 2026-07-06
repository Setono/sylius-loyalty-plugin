<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Event\Trigger\EarningTriggerEvent;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderCheckoutStates;

/**
 * The default resolution chain: the explicit channel on the event, then the shop channel
 * context (when dispatched during a shop request), then the channel of the customer's latest
 * completed order, then the single enabled channel if exactly one exists.
 */
final class TriggerChannelResolver implements TriggerChannelResolverInterface
{
    /**
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     * @param class-string $orderClass
     */
    public function __construct(
        private readonly ChannelContextInterface $channelContext,
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $orderClass,
    ) {
    }

    public function resolve(EarningTriggerEvent $event): ?ChannelInterface
    {
        $channel = $event->getChannel();
        if (null !== $channel) {
            return $channel;
        }

        try {
            return $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
            // not in a shop request
        }

        $channel = $this->latestOrderChannel($event);
        if (null !== $channel) {
            return $channel;
        }

        $enabledChannels = $this->channelRepository->findBy(['enabled' => true]);
        if (1 === count($enabledChannels)) {
            return $enabledChannels[0];
        }

        return null;
    }

    private function latestOrderChannel(EarningTriggerEvent $event): ?ChannelInterface
    {
        $order = $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from($this->orderClass, 'o')
            ->andWhere('o.customer = :customer')
            ->andWhere('o.checkoutState = :checkoutState')
            ->setParameter('customer', $event->getCustomer())
            ->setParameter('checkoutState', OrderCheckoutStates::STATE_COMPLETED)
            ->orderBy('o.checkoutCompletedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $order instanceof OrderInterface ? $order->getChannel() : null;
    }
}
