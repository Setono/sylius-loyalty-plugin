<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Redemption;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;

/**
 * Converts between points and currency amounts using the program's redemption conversion
 * (P points are worth A minor units). Points are always clamped down to clean multiples of P
 * so the points debited and the discount granted correspond exactly — no fractional value
 * leaks in either direction.
 */
interface PointsConverterInterface
{
    /**
     * The discount (minor units) for the given points. The points must be a clean multiple of
     * the conversion's points unit.
     */
    public function amountFromPoints(int $points, LoyaltyProgramInterface $program): int;

    /**
     * The largest number of points whose value does not exceed the given amount.
     */
    public function pointsFromAmount(int $amount, LoyaltyProgramInterface $program): int;

    /**
     * Clamps down to the nearest clean multiple of the conversion's points unit.
     */
    public function clampToCleanMultiple(int $points, LoyaltyProgramInterface $program): int;
}
