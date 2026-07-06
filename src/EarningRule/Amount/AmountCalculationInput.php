<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Amount;

use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;

/**
 * The basis a rule claimed during evaluation: the eligible amount (minor units) and the number
 * of matching units. Order-scoped rules always claim with units = 1; item-scoped rules claim
 * the matching items' quantities.
 */
final class AmountCalculationInput
{
    public function __construct(
        public readonly int $basisAmount,
        public readonly int $units,
        public readonly EarningContext $context,
    ) {
    }
}
