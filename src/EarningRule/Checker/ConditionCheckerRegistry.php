<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Checker;

use Setono\CompositeCompilerPass\CompositeService;

/**
 * @extends CompositeService<ConditionCheckerInterface>
 */
final class ConditionCheckerRegistry extends CompositeService implements ConditionCheckerRegistryInterface
{
    public function get(string $type): ?ConditionCheckerInterface
    {
        foreach ($this->services as $checker) {
            if ($checker->getType() === $type) {
                return $checker;
            }
        }

        return null;
    }

    public function all(): array
    {
        return $this->services;
    }
}
