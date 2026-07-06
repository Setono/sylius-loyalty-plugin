<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Checker;

interface ConditionCheckerRegistryInterface
{
    public function get(string $type): ?ConditionCheckerInterface;

    /**
     * @return list<ConditionCheckerInterface>
     */
    public function all(): array;
}
