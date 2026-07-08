<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Earning\Trigger;

use Setono\SyliusLoyaltyPlugin\Message\Command\AwardOrderPoints;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class AwardOrderPointsTrigger implements AwardOrderPointsTriggerInterface
{
    public function __construct(
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    public function trigger(OrderInterface $order, string $moment): void
    {
        $channel = $order->getChannel();
        if (null === $channel) {
            return;
        }

        if ($moment !== $this->programProvider->getProgram($channel)->getAwardOrderPointsAt()) {
            return;
        }

        $this->commandBus->dispatch(new AwardOrderPoints($order));
    }
}
