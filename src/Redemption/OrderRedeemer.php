<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Redemption;

use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class OrderRedeemer implements OrderRedeemerInterface
{
    public function __construct(
        private readonly LoyaltyAccountProviderInterface $accountProvider,
        private readonly LoyaltyLedgerInterface $ledger,
    ) {
    }

    public function redeem(OrderInterface $order): void
    {
        $customer = $order->getCustomer();
        $channel = $order->getChannel();
        if (!$customer instanceof CustomerInterface || null === $channel) {
            return;
        }

        $points = $this->requestedPoints($order);
        if ($points <= 0) {
            return;
        }

        $this->ledger->redeem($this->accountProvider->getAccount($customer, $channel), $order, $points);
    }

    public function rollback(OrderInterface $order): void
    {
        $this->ledger->rollbackRedemption($order);
    }

    private function requestedPoints(OrderInterface $order): int
    {
        $points = 0;
        foreach ($order->getAdjustments(RedemptionOrderProcessor::ADJUSTMENT_TYPE) as $adjustment) {
            $value = $adjustment->getDetails()['points'] ?? 0;
            if (is_int($value)) {
                $points += $value;
            }
        }

        return $points;
    }
}
