<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression;

use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;
use Setono\SyliusLoyaltyPlugin\Exception\InvalidExpressionException;
use Setono\SyliusLoyaltyPlugin\Expression\Function\ExpressionFunctionInterface;
use Setono\SyliusLoyaltyPlugin\Expression\Function\ExpressionFunctionRegistryInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;

final class ExpressionEvaluator implements ExpressionEvaluatorInterface
{
    /**
     * Carries the earning context into function evaluators without exposing it as an
     * expression variable.
     */
    private const CONTEXT_VARIABLE = '_context';

    private ?ExpressionLanguage $expressionLanguage = null;

    public function __construct(
        private readonly ExpressionFunctionRegistryInterface $functions,
        private readonly ExpressionValidatorInterface $validator,
    ) {
    }

    public function evaluate(string $expression, EarningContext $context, ?int $basisOverride = null): mixed
    {
        $this->validator->validate($expression);

        try {
            // Expressions evaluate against the entities directly (rules are authored by
            // administrators) through the generic getter bridge; the catalog whitelist is
            // enforced at save and lint time by the validator
            return $this->expressionLanguage()->evaluate($expression, [
                self::CONTEXT_VARIABLE => $context,
                'order' => null === $context->order ? null : new EntityAccess($context->order),
                'basis' => $basisOverride ?? $context->getBasis(),
                'customer' => null === $context->customer ? null : new EntityAccess($context->customer),
                'account' => null === $context->account ? null : new EntityAccess($context->account),
                'channel' => new EntityAccess($context->channel),
                'context' => (object) $context->context,
            ]);
        } catch (SyntaxError $e) {
            throw new InvalidExpressionException($e->getMessage(), 0, $e);
        }
    }

    private function expressionLanguage(): ExpressionLanguage
    {
        if (null === $this->expressionLanguage) {
            $expressionLanguage = new ExpressionLanguage();

            foreach ($this->functions->all() as $function) {
                $this->register($expressionLanguage, $function);
            }

            $this->expressionLanguage = $expressionLanguage;
        }

        return $this->expressionLanguage;
    }

    private function register(ExpressionLanguage $expressionLanguage, ExpressionFunctionInterface $function): void
    {
        $expressionLanguage->register(
            $function->getName(),
            static function (): string {
                throw new \LogicException('Loyalty expressions are never compiled');
            },
            static function (array $variables, mixed ...$arguments) use ($function): mixed {
                $context = $variables[self::CONTEXT_VARIABLE];
                \assert($context instanceof EarningContext);

                return $function->evaluate($context, ...$arguments);
            },
        );
    }
}
