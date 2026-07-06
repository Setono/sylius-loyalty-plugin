<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Checker;

use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;

/**
 * A condition type for earning rules. Implementations are registered with the
 * "setono_sylius_loyalty.earning_condition" tag (autoconfigured) and appear in the rule form
 * automatically.
 */
interface ConditionCheckerInterface
{
    public function getType(): string;

    /**
     * A translation key for the rule form's condition type select.
     */
    public function getLabel(): string;

    /**
     * The form type rendering this condition's configuration, or null if it has none.
     *
     * @return class-string|null
     */
    public function getConfigurationFormType(): ?string;

    /**
     * @param array<string, mixed> $configuration
     */
    public function check(array $configuration, EarningContext $context): bool;
}
