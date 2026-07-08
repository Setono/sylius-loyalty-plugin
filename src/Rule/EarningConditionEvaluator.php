<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule;

use Setono\SyliusLoyaltyPlugin\Model\EarningRuleConditionInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Setono\SyliusLoyaltyPlugin\Rule\Condition\EarningConditionInterface;

/**
 * Decides whether a rule's conditions pass for a given context, honouring the rule's `conditionsMatch`
 * (`all` vs `any`). A rule with no conditions always matches. An unknown condition type fails closed —
 * points are never awarded on a condition the system cannot evaluate.
 */
final class EarningConditionEvaluator implements EarningConditionEvaluatorInterface
{
    /** @var array<string, EarningConditionInterface> */
    private array $conditions = [];

    /**
     * @param iterable<EarningConditionInterface> $conditions
     */
    public function __construct(iterable $conditions)
    {
        foreach ($conditions as $condition) {
            $this->conditions[$condition->getType()] = $condition;
        }
    }

    public function matches(EarningRuleInterface $rule, RuleEvaluationContext $context): bool
    {
        $conditions = $rule->getConditions();
        if ($conditions->isEmpty()) {
            return true;
        }

        $matchAny = EarningRuleInterface::CONDITIONS_MATCH_ANY === $rule->getConditionsMatch();

        foreach ($conditions as $condition) {
            $satisfied = $this->isSatisfied($condition, $context);

            if ($matchAny && $satisfied) {
                return true;
            }

            if (!$matchAny && !$satisfied) {
                return false;
            }
        }

        // reached the end: for `all` every condition passed; for `any` none did
        return !$matchAny;
    }

    private function isSatisfied(EarningRuleConditionInterface $condition, RuleEvaluationContext $context): bool
    {
        $type = $condition->getType();
        if (null === $type || !isset($this->conditions[$type])) {
            return false;
        }

        return $this->conditions[$type]->isSatisfied($context, $condition->getConfiguration());
    }
}
