<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Earning\Trigger;

use Setono\SyliusLoyaltyPlugin\Message\Command\ClawbackOrderPoints;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

/**
 * Dispatches a clawback when an order is cancelled or its payment is refunded. Unlike the award trigger
 * there is no moment to match — cancellation and refund always reverse earned points; the ledger
 * clawback is a no-op when the order earned nothing. Wired for both state-machine adapters (workflow
 * methods take a CompletedEvent, winzou methods take the order directly); each only fires under the
 * adapter the application applies transitions through, and the ledger's idempotency covers any overlap.
 */
final class ClawbackOrderPointsStateMachineListener
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    public function onWorkflowOrderCancelled(CompletedEvent $event): void
    {
        $this->fromWorkflow($event);
    }

    public function onWorkflowPaymentRefunded(CompletedEvent $event): void
    {
        $this->fromWorkflow($event);
    }

    public function onWinzouOrderCancelled(OrderInterface $order): void
    {
        $this->commandBus->dispatch(new ClawbackOrderPoints($order));
    }

    public function onWinzouPaymentRefunded(OrderInterface $order): void
    {
        $this->commandBus->dispatch(new ClawbackOrderPoints($order));
    }

    private function fromWorkflow(CompletedEvent $event): void
    {
        $subject = $event->getSubject();
        if ($subject instanceof OrderInterface) {
            $this->commandBus->dispatch(new ClawbackOrderPoints($subject));
        }
    }
}
