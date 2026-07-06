<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EventListener;

use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

/**
 * Restores redeemed points when an order is cancelled (before payment). Abandoned unpaid
 * orders are closed out operationally by Sylius core's sylius:cancel-unpaid-orders cron,
 * whose cancellation lands here too.
 */
final class RollbackRedemptionListener
{
    public function __construct(
        private readonly LoyaltyLedgerInterface $ledger,
    ) {
    }

    /**
     * The winzou callback entry point.
     */
    public function rollback(OrderInterface $order): void
    {
        $this->ledger->rollbackRedeem($order);
    }

    /**
     * The symfony/workflow entry point.
     */
    public function onWorkflowCompleted(CompletedEvent $event): void
    {
        $order = $event->getSubject();
        if ($order instanceof OrderInterface) {
            $this->rollback($order);
        }
    }
}
