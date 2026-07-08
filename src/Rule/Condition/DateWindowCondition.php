<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule\Condition;

use Setono\SyliusLoyaltyPlugin\Rule\RuleEvaluationContext;

final class DateWindowCondition implements EarningConditionInterface
{
    public function getType(): string
    {
        return 'date_window';
    }

    public function isSatisfied(RuleEvaluationContext $context, array $configuration): bool
    {
        $now = $context->getEvaluatedAt();

        $from = $this->toDateTime($configuration['from'] ?? null);
        if (null !== $from && $now < $from) {
            return false;
        }

        $to = $this->toDateTime($configuration['to'] ?? null);
        if (null !== $to && $now > $to) {
            return false;
        }

        return true;
    }

    private function toDateTime(mixed $value): ?\DateTimeInterface
    {
        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        if (is_string($value) && '' !== $value) {
            return new \DateTimeImmutable($value);
        }

        return null;
    }
}
