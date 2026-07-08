<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Redemption;

use Sylius\Component\Core\Model\OrderInterface;

final class RedemptionAdjustments
{
    private function __construct()
    {
    }

    /**
     * The loyalty points recorded in an order's redemption adjustments — what the customer was shown and
     * what should be debited at checkout.
     */
    public static function points(OrderInterface $order): int
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
