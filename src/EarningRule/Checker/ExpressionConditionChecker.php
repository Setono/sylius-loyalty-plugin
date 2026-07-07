<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Checker;

use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;
use Setono\SyliusLoyaltyPlugin\Expression\ExpressionEvaluatorInterface;

/**
 * A condition written as a sandboxed expression that must evaluate truthy.
 */
final class ExpressionConditionChecker implements ConditionCheckerInterface
{
    public const TYPE = 'expression';

    public function __construct(
        private readonly ExpressionEvaluatorInterface $expressionEvaluator,
    ) {
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return 'setono_sylius_loyalty.form.earning_rule.condition.expression';
    }

    public function getConfigurationFormType(): ?string
    {
        return null; // set when the admin forms ship
    }

    public function check(array $configuration, EarningContext $context): bool
    {
        $expression = $configuration['expression'] ?? null;
        if (!is_string($expression) || '' === $expression) {
            return false;
        }

        return (bool) $this->expressionEvaluator->evaluate($expression, $context);
    }

    public function requiresCustomer(): bool
    {
        return true;
    }

    public function requiresCart(): bool
    {
        return true;
    }
}
