<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Amount;

/**
 * Multiplies the summed result of the base (non-multiplier) rules. Multiplier rules are
 * order-scoped only and evaluate after the base rules; the evaluator applies the factor to the
 * base sum — calculate() therefore returns the factor itself, not points.
 */
final class MultiplierAmountCalculator implements AmountCalculatorInterface
{
    public const TYPE = 'multiplier';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return 'setono_sylius_loyalty.form.earning_rule.amount.multiplier';
    }

    public function getConfigurationFormType(): ?string
    {
        return null; // set when the admin forms ship
    }

    public function calculate(array $configuration, AmountCalculationInput $input): float
    {
        return self::factor($configuration);
    }

    /**
     * @param array<string, mixed> $configuration
     */
    public static function factor(array $configuration): float
    {
        $factor = $configuration['factor'] ?? null;
        if (!is_int($factor) && !is_float($factor)) {
            return 1.0;
        }

        return (float) $factor;
    }
}
