<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Amount;

/**
 * An amount type for earning rules. Implementations are registered with the
 * "setono_sylius_loyalty.earning_amount" tag (autoconfigured) and appear in the rule form
 * automatically.
 */
interface AmountCalculatorInterface
{
    public function getType(): string;

    /**
     * A translation key for the rule form's amount type select.
     */
    public function getLabel(): string;

    /**
     * The form type rendering this amount's configuration, or null if it has none.
     *
     * @return class-string|null
     */
    public function getConfigurationFormType(): ?string;

    /**
     * Returns the (unrounded) points for the claimed basis. The program's rounding is applied
     * once on the final total, not per rule.
     *
     * @param array<string, mixed> $configuration
     */
    public function calculate(array $configuration, AmountCalculationInput $input): float;
}
