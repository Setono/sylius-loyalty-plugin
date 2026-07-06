<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Redemption;

use Setono\SyliusLoyaltyPlugin\LoyaltyAdjustmentTypes;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class AppliedPointsProvider implements AppliedPointsProviderInterface
{
    public function __construct(
        private readonly LoyaltyProgramProviderInterface $programProvider,
    ) {
    }

    public function getAppliedPoints(OrderInterface $order): int
    {
        $discount = -$order->getAdjustmentsTotalRecursively(LoyaltyAdjustmentTypes::REDEMPTION);
        if ($discount <= 0) {
            return 0;
        }

        $channel = $order->getChannel();
        if (null === $channel) {
            return 0;
        }

        return $this->pointsFromDiscount($discount, $this->programProvider->getByChannel($channel));
    }

    /**
     * The discount is a clean multiple of the conversion amount by construction, so this is
     * exact: discount / A * P.
     */
    private function pointsFromDiscount(int $discount, LoyaltyProgramInterface $program): int
    {
        $amountUnit = max(1, $program->getRedemptionConversionAmount());

        return intdiv($discount, $amountUnit) * max(1, $program->getRedemptionConversionPoints());
    }
}
