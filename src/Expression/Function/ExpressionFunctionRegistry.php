<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression\Function;

use Setono\CompositeCompilerPass\CompositeService;

/**
 * @extends CompositeService<ExpressionFunctionInterface>
 */
final class ExpressionFunctionRegistry extends CompositeService implements ExpressionFunctionRegistryInterface
{
    public function get(string $name): ?ExpressionFunctionInterface
    {
        foreach ($this->services as $function) {
            if ($function->getName() === $name) {
                return $function;
            }
        }

        return null;
    }

    public function all(): array
    {
        return $this->services;
    }
}
