<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule;

use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Setono\SyliusLoyaltyPlugin\Rule\Amount\EarningAmountContext;

interface EarningAmountEvaluatorInterface
{
    /**
     * The base points a rule contributes for the given basis. Multiplier rules yield 0 here — they
     * scale the summed base result in the earning calculator, not through a base amount.
     */
    public function calculate(EarningRuleInterface $rule, EarningAmountContext $context): int;
}
