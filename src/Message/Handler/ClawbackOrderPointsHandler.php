<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Message\Handler;

use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Message\Command\ClawbackOrderPoints;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;

final class ClawbackOrderPointsHandler
{
    /**
     * @param OrderRepositoryInterface<OrderInterface> $orderRepository
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly LoyaltyLedgerInterface $ledger,
    ) {
    }

    public function __invoke(ClawbackOrderPoints $message): void
    {
        $order = $this->orderRepository->find($message->order);
        if (!$order instanceof OrderInterface) {
            return;
        }

        $channel = $order->getChannel();
        if (null === $channel) {
            return;
        }

        $this->ledger->clawback($order, $this->programProvider->getProgram($channel)->getClawbackPolicy());
    }
}
