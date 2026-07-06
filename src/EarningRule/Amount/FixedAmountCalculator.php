<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Amount;

/**
 * X points. Under item scopes the amount is awarded per matching unit (quantity-aware); under
 * order scope it is a flat amount (the evaluator claims with units = 1).
 */
final class FixedAmountCalculator implements AmountCalculatorInterface
{
    public const TYPE = 'fixed';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return 'setono_sylius_loyalty.form.earning_rule.amount.fixed';
    }

    public function getConfigurationFormType(): ?string
    {
        return null; // set when the admin forms ship
    }

    public function calculate(array $configuration, AmountCalculationInput $input): float
    {
        $points = $configuration['points'] ?? null;
        if (!is_int($points) && !is_float($points)) {
            return 0.0;
        }

        return (float) $points * $input->units;
    }
}
