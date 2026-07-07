<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule;

use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;

interface EarningConditionEvaluatorInterface
{
    public function matches(EarningRuleInterface $rule, RuleEvaluationContext $context): bool;
}
