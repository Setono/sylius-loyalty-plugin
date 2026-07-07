<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Amount;

use Psr\Log\LoggerInterface;
use Setono\SyliusLoyaltyPlugin\Expression\ExpressionEvaluatorInterface;

/**
 * An amount computed by a sandboxed expression, e.g.
 * "basis > 50000 ? floor(basis / 50) : floor(basis / 100)". The "basis" variable holds the
 * rule's claimed basis.
 */
final class ExpressionAmountCalculator implements AmountCalculatorInterface
{
    public const TYPE = 'expression';

    public function __construct(
        private readonly ExpressionEvaluatorInterface $expressionEvaluator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return 'setono_sylius_loyalty.form.earning_rule.amount.expression';
    }

    public function getConfigurationFormType(): ?string
    {
        return null; // set when the admin forms ship
    }

    public function calculate(array $configuration, AmountCalculationInput $input): float
    {
        $expression = $configuration['expression'] ?? null;
        if (!is_string($expression) || '' === $expression) {
            return 0.0;
        }

        $result = $this->expressionEvaluator->evaluate($expression, $input->context, $input->basisAmount);

        if (!is_int($result) && !is_float($result)) {
            $this->logger->warning(sprintf(
                '[Loyalty] The amount expression "%s" evaluated to a non-numeric value (%s); the rule contributes nothing',
                $expression,
                get_debug_type($result),
            ));

            return 0.0;
        }

        return (float) $result;
    }
}
