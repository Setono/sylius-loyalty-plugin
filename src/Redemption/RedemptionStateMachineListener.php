<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Redemption;

use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

/**
 * Debits the redeemed points when checkout completes, and returns them when the order is cancelled. The
 * debit is synchronous (it happens in the same request as the transition, inside the ledger lock) so the
 * balance is authoritative the moment the order is placed. Wired for both state-machine adapters; each
 * only fires under the adapter the application applies transitions through, and the ledger's idempotency
 * covers any overlap.
 */
final class RedemptionStateMachineListener
{
    public function __construct(
        private readonly OrderRedeemerInterface $redeemer,
    ) {
    }

    public function onWorkflowCheckoutCompleted(CompletedEvent $event): void
    {
        $subject = $event->getSubject();
        if ($subject instanceof OrderInterface) {
            $this->redeemer->redeem($subject);
        }
    }

    public function onWorkflowOrderCancelled(CompletedEvent $event): void
    {
        $subject = $event->getSubject();
        if ($subject instanceof OrderInterface) {
            $this->redeemer->rollback($subject);
        }
    }

    public function onWinzouCheckoutCompleted(OrderInterface $order): void
    {
        $this->redeemer->redeem($order);
    }

    public function onWinzouOrderCancelled(OrderInterface $order): void
    {
        $this->redeemer->rollback($order);
    }
}
