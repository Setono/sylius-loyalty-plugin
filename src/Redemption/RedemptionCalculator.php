<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Redemption;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;

final class RedemptionCalculator implements RedemptionCalculatorInterface
{
    public function calculate(
        int $requestedPoints,
        int $availableBalance,
        int $orderTotal,
        LoyaltyProgramInterface $program,
    ): int {
        $conversionPoints = $program->getRedemptionConversionPoints();
        $conversionAmount = $program->getRedemptionConversionAmount();
        if ($conversionPoints <= 0 || $conversionAmount <= 0) {
            return 0;
        }

        $applied = max(0, min($requestedPoints, $availableBalance));

        // Cap to the program's maximum share of the order value, expressed back in points.
        $percent = max(0, min(100, $program->getMaxRedeemPercentOfOrder()));
        $maxAmount = intdiv(max(0, $orderTotal) * $percent, 100);
        $maxPoints = intdiv($maxAmount, $conversionAmount) * $conversionPoints;
        $applied = min($applied, $maxPoints);

        // Round down to a clean conversion multiple so the redemption maps to a whole money amount.
        $applied = intdiv($applied, $conversionPoints) * $conversionPoints;

        if ($applied < $program->getMinRedeemPoints()) {
            return 0;
        }

        return $applied;
    }

    public function amount(int $appliedPoints, LoyaltyProgramInterface $program): int
    {
        $conversionPoints = $program->getRedemptionConversionPoints();
        if ($conversionPoints <= 0) {
            return 0;
        }

        return intdiv($appliedPoints, $conversionPoints) * $program->getRedemptionConversionAmount();
    }
}
