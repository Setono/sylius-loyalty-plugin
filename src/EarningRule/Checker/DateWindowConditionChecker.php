<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Checker;

use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;

/**
 * Passes when the evaluation time falls inside the configured window. Dates are entered and
 * evaluated in the application's configured timezone.
 */
final class DateWindowConditionChecker implements ConditionCheckerInterface
{
    public const TYPE = 'date_window';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return 'setono_sylius_loyalty.form.earning_rule.condition.date_window';
    }

    public function getConfigurationFormType(): ?string
    {
        return null; // set when the admin forms ship
    }

    public function check(array $configuration, EarningContext $context): bool
    {
        $now = $context->getNow();

        $from = self::parseDate($configuration['from'] ?? null);
        if (null !== $from && $now < $from) {
            return false;
        }

        $until = self::parseDate($configuration['until'] ?? null);
        if (null !== $until && $now > $until) {
            return false;
        }

        return true;
    }

    private static function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || '' === $value) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
