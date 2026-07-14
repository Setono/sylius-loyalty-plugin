<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Earning;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;

/**
 * Resolves the channel of the customer's most recent order.
 */
final class LastOrderChannelResolver implements TriggerChannelResolverInterface
{
    /**
     * @param OrderRepositoryInterface<OrderInterface> $orderRepository
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {
    }

    public function resolve(CustomerInterface $customer): iterable
    {
        $orders = $this->orderRepository->findBy(['customer' => $customer], ['id' => 'DESC'], 1);
        $order = $orders[0] ?? null;
        if (!$order instanceof OrderInterface) {
            return;
        }

        $channel = $order->getChannel();
        if ($channel instanceof ChannelInterface) {
            yield $channel;
        }
    }
}
