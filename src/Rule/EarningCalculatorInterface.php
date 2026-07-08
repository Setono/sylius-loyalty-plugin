<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule;

use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Sylius\Component\Core\Model\OrderInterface;

interface EarningCalculatorInterface
{
    /**
     * Computes the points an order earns from the given rules: applicable base rules are summed (or the
     * highest-priority non-stackable one wins), multipliers scale the sum, and the program's rounding
     * is applied.
     *
     * @param iterable<EarningRuleInterface> $rules the candidate rules (typically the channel's enabled
     *                                              order-eligible rules)
     */
    public function calculate(
        OrderInterface $order,
        LoyaltyProgramInterface $program,
        iterable $rules,
        \DateTimeInterface $evaluatedAt,
    ): EarningResult;
}
