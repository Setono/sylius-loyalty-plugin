<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EventListener;

use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Model\ReferralInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Repository\ReferralRepositoryInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

/**
 * Claws back the points originally earned for an order when it is cancelled or fully
 * refunded. A no-op when the order earned nothing; the (type, earn) unique constraint makes
 * replays safe. Partial refunds are deliberately not handled here — projects call
 * LoyaltyLedgerInterface::clawback() from their own refund mechanism.
 */
final class ClawbackListener
{
    public function __construct(
        private readonly LoyaltyTransactionRepositoryInterface $transactionRepository,
        private readonly ReferralRepositoryInterface $referralRepository,
        private readonly LoyaltyLedgerInterface $ledger,
    ) {
    }

    /**
     * The winzou callback entry point (sylius_order/cancel and sylius_order_payment/refund).
     */
    public function clawback(OrderInterface $order): void
    {
        $earn = $this->transactionRepository->findEarnOrderTransaction($order);
        if (null !== $earn && $earn->getPoints() > 0) {
            $this->ledger->clawback($order, $earn->getPoints());
        }

        $this->clawbackReferralRewards($order);
    }

    /**
     * Cancelling or refunding a referral's qualifying order claws back both parties' rewards
     * (idempotent per credit via the (type, earn) unique constraint).
     */
    private function clawbackReferralRewards(OrderInterface $order): void
    {
        $referral = $this->referralRepository->findOneBy(['refereeFirstOrder' => $order]);
        if (!$referral instanceof ReferralInterface || ReferralInterface::STATUS_REWARDED !== $referral->getStatus()) {
            return;
        }

        foreach ($this->transactionRepository->findEarnReferralTransactions($referral) as $credit) {
            if ($credit->getPoints() > 0) {
                $this->ledger->clawbackCredit($credit, $order);
            }
        }
    }

    /**
     * The symfony/workflow entry point.
     */
    public function onWorkflowCompleted(CompletedEvent $event): void
    {
        $order = $event->getSubject();
        if ($order instanceof OrderInterface) {
            $this->clawback($order);
        }
    }
}
