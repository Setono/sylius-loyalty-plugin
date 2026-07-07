<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression\Function;

use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;

/**
 * The ISO-8601 weekday of the evaluation time (1 = Monday … 7 = Sunday).
 */
final class DayOfWeekFunction implements ExpressionFunctionInterface
{
    public function getName(): string
    {
        return 'day_of_week';
    }

    public function getSignature(): string
    {
        return 'day_of_week(): int';
    }

    public function getDescription(): string
    {
        return 'setono_sylius_loyalty.expression.function.day_of_week';
    }

    public function evaluate(EarningContext $context, mixed ...$arguments): mixed
    {
        return (int) $context->getNow()->format('N');
    }
}
