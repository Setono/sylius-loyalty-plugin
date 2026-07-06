<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EventListener;

use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Redemption\AppliedPointsProviderInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;

/**
 * Writes the redemption debit synchronously when the order completes checkout — the one
 * deliberately synchronous ledger write, since it gates the order. Hooked as a winzou
 * "before" callback and a symfony/workflow "transition" listener: both fire before the
 * transition applies, so a thrown InsufficientBalanceException or LedgerConflictException
 * aborts the completion (after/completed hooks could not).
 */
final class RedeemPointsListener
{
    public function __construct(
        private readonly AppliedPointsProviderInterface $appliedPointsProvider,
        private readonly LoyaltyLedgerInterface $ledger,
    ) {
    }

    /**
     * The winzou callback entry point.
     */
    public function redeem(OrderInterface $order): void
    {
        $appliedPoints = $this->appliedPointsProvider->getAppliedPoints($order);
        if ($appliedPoints <= 0) {
            return;
        }

        // Balance and enabled state are re-validated inside the account lock
        $this->ledger->redeem($order, $appliedPoints);
    }

    /**
     * The symfony/workflow entry point.
     */
    public function onWorkflowTransition(TransitionEvent $event): void
    {
        $order = $event->getSubject();
        if ($order instanceof OrderInterface) {
            $this->redeem($order);
        }
    }
}
