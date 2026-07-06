<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Amount;

interface AmountCalculatorRegistryInterface
{
    public function get(string $type): ?AmountCalculatorInterface;

    /**
     * @return list<AmountCalculatorInterface>
     */
    public function all(): array;
}
