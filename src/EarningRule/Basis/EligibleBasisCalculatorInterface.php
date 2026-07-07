<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Basis;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * Computes the eligible earning basis per the program's earningBasis and includeTaxes
 * settings. Item totals are the discounted amounts (promotions are already distributed to
 * units by Sylius core, and the loyalty redemption adjustment reduces them too — no earning
 * on the redeemed part).
 */
interface EligibleBasisCalculatorInterface
{
    public function calculate(OrderInterface $order, LoyaltyProgramInterface $program): EligibleBasis;
}
