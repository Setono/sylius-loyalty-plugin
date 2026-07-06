<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression\Function;

interface ExpressionFunctionRegistryInterface
{
    public function get(string $name): ?ExpressionFunctionInterface;

    /**
     * @return list<ExpressionFunctionInterface>
     */
    public function all(): array;
}
