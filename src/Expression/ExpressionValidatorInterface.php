<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression;

use Setono\SyliusLoyaltyPlugin\Exception\InvalidExpressionException;

/**
 * Parses an expression and walks its AST against the catalog whitelist. Used on save, by the
 * admin lint endpoint, and defensively before evaluation — always the same checks.
 */
interface ExpressionValidatorInterface
{
    /**
     * @param string|null $trigger narrows the variable catalog to the rule's trigger (the
     *        order/basis variables only exist under the built-in order trigger)
     *
     * @throws InvalidExpressionException
     */
    public function validate(string $expression, ?string $trigger = null): void;
}
