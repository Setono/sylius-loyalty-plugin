<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Redemption;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;

final class PointsConverter implements PointsConverterInterface
{
    public function amountFromPoints(int $points, LoyaltyProgramInterface $program): int
    {
        $pointsUnit = max(1, $program->getRedemptionConversionPoints());

        return intdiv($points, $pointsUnit) * $program->getRedemptionConversionAmount();
    }

    public function pointsFromAmount(int $amount, LoyaltyProgramInterface $program): int
    {
        $amountUnit = max(1, $program->getRedemptionConversionAmount());

        return intdiv($amount, $amountUnit) * max(1, $program->getRedemptionConversionPoints());
    }

    public function clampToCleanMultiple(int $points, LoyaltyProgramInterface $program): int
    {
        $pointsUnit = max(1, $program->getRedemptionConversionPoints());

        return $points - ($points % $pointsUnit);
    }
}
