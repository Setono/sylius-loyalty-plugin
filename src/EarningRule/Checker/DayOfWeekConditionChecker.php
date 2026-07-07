<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Checker;

use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;

/**
 * Passes when the evaluation time falls on one of the configured ISO-8601 weekdays
 * (1 = Monday … 7 = Sunday).
 */
final class DayOfWeekConditionChecker implements ConditionCheckerInterface
{
    public const TYPE = 'day_of_week';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return 'setono_sylius_loyalty.form.earning_rule.condition.day_of_week';
    }

    public function getConfigurationFormType(): ?string
    {
        return null; // set when the admin forms ship
    }

    public function check(array $configuration, EarningContext $context): bool
    {
        $days = array_map(
            static fn (mixed $day): int => is_numeric($day) ? (int) $day : 0,
            (array) ($configuration['days'] ?? []),
        );

        return in_array((int) $context->getNow()->format('N'), $days, true);
    }

    public function requiresCustomer(): bool
    {
        return false;
    }

    public function requiresCart(): bool
    {
        return false;
    }
}
