<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule\Condition;

use Setono\SyliusLoyaltyPlugin\Rule\RuleEvaluationContext;

final class DayOfWeekCondition implements EarningConditionInterface
{
    public function getType(): string
    {
        return 'day_of_week';
    }

    public function isSatisfied(RuleEvaluationContext $context, array $configuration): bool
    {
        $days = $configuration['days'] ?? [];
        if (!is_array($days)) {
            return false;
        }

        // ISO-8601 day of the week: 1 (Monday) through 7 (Sunday)
        $today = (int) $context->getEvaluatedAt()->format('N');

        foreach ($days as $day) {
            if (is_numeric($day) && (int) $day === $today) {
                return true;
            }
        }

        return false;
    }
}
