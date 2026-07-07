<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression;

use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;
use Setono\SyliusLoyaltyPlugin\Exception\InvalidExpressionException;

interface ExpressionEvaluatorInterface
{
    /**
     * Evaluates a sandboxed expression against the earning context. Expressions only ever see
     * curated view objects, never entities.
     *
     * @param int|null $basisOverride replaces the "basis" variable — used for item-scoped
     *        rules whose claimed basis differs from the full order basis
     *
     * @throws InvalidExpressionException
     */
    public function evaluate(string $expression, EarningContext $context, ?int $basisOverride = null): mixed;
}
