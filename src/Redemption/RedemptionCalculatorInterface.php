<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Redemption;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;

interface RedemptionCalculatorInterface
{
    /**
     * The number of points that may actually be redeemed on an order, given the customer's requested
     * amount and available balance. The result is clamped to the balance, to the program's maximum
     * percentage of the order, and down to a clean multiple of the conversion rate (so it always maps to
     * a whole money amount); it is zero when the outcome would be below the program's minimum.
     */
    public function calculate(
        int $requestedPoints,
        int $availableBalance,
        int $orderTotal,
        LoyaltyProgramInterface $program,
    ): int;

    /**
     * The money amount (in the order's minor unit) that the given number of applied points converts to.
     */
    public function amount(int $appliedPoints, LoyaltyProgramInterface $program): int;
}
