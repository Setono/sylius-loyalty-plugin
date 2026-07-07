<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Amount;

/**
 * X points per Y minor units of eligible (claimed) basis.
 */
final class PerAmountCalculator implements AmountCalculatorInterface
{
    public const TYPE = 'per_amount';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return 'setono_sylius_loyalty.form.earning_rule.amount.per_amount';
    }

    public function getConfigurationFormType(): ?string
    {
        return null; // set when the admin forms ship
    }

    public function calculate(array $configuration, AmountCalculationInput $input): float
    {
        $points = $configuration['points'] ?? null;
        $perAmount = $configuration['per_amount'] ?? null;

        if ((!is_int($points) && !is_float($points)) || !is_int($perAmount) || $perAmount < 1) {
            return 0.0;
        }

        return $input->basisAmount / $perAmount * (float) $points;
    }
}
