<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule;

use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;

/**
 * Evaluates earning rules against a context: conditions per the rule's all/any matching,
 * exclusive per-item claiming with product > taxon > order specificity, base-rule stacking,
 * multiplier rules, and the program's rounding. Read-only — never writes anything.
 */
interface EarningRuleEvaluatorInterface
{
    /**
     * @param iterable<EarningRuleInterface> $rules rules sharing the same trigger; disabled or
     *        out-of-window rules are ignored, dry-run rules are diverted to the dry-run result
     */
    public function evaluate(iterable $rules, EarningContext $context, LoyaltyProgramInterface $program): EvaluationResult;
}
