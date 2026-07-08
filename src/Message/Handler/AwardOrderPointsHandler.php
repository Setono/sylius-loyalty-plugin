<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Message\Handler;

use Setono\SyliusLoyaltyPlugin\Earning\OrderPointsAwarderInterface;
use Setono\SyliusLoyaltyPlugin\Message\Command\AwardOrderPoints;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;

final class AwardOrderPointsHandler
{
    /**
     * @param OrderRepositoryInterface<OrderInterface> $orderRepository
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderPointsAwarderInterface $awarder,
    ) {
    }

    public function __invoke(AwardOrderPoints $message): void
    {
        $order = $this->orderRepository->find($message->order);
        if (!$order instanceof OrderInterface) {
            return;
        }

        $this->awarder->award($order);
    }
}
