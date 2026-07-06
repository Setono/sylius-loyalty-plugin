<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Amount;

use Setono\CompositeCompilerPass\CompositeService;

/**
 * @extends CompositeService<AmountCalculatorInterface>
 */
final class AmountCalculatorRegistry extends CompositeService implements AmountCalculatorRegistryInterface
{
    public function get(string $type): ?AmountCalculatorInterface
    {
        foreach ($this->services as $calculator) {
            if ($calculator->getType() === $type) {
                return $calculator;
            }
        }

        return null;
    }

    public function all(): array
    {
        return $this->services;
    }
}
