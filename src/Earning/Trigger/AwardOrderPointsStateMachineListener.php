<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Earning\Trigger;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

/**
 * Bridges both state-machine adapters to the trigger. The symfony/workflow methods take a workflow
 * CompletedEvent; the winzou methods take the order directly (as configured by a winzou `after` callback
 * with `args: ['object']`). Both are wired unconditionally: each only fires under the adapter the app
 * applies transitions through, and the ledger's idempotency covers any overlap.
 */
final class AwardOrderPointsStateMachineListener
{
    public function __construct(
        private readonly AwardOrderPointsTriggerInterface $trigger,
    ) {
    }

    public function onWorkflowPaymentPaid(CompletedEvent $event): void
    {
        $this->fromWorkflow($event, LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_PAYMENT_PAID);
    }

    public function onWorkflowOrderFulfilled(CompletedEvent $event): void
    {
        $this->fromWorkflow($event, LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_ORDER_FULFILLED);
    }

    public function onWinzouPaymentPaid(OrderInterface $order): void
    {
        $this->trigger->trigger($order, LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_PAYMENT_PAID);
    }

    public function onWinzouOrderFulfilled(OrderInterface $order): void
    {
        $this->trigger->trigger($order, LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_ORDER_FULFILLED);
    }

    private function fromWorkflow(CompletedEvent $event, string $moment): void
    {
        $subject = $event->getSubject();
        if ($subject instanceof OrderInterface) {
            $this->trigger->trigger($subject, $moment);
        }
    }
}
