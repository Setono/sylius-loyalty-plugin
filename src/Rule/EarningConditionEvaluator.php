<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule;

use Psr\Container\ContainerInterface;
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
    /**
     * @param ContainerInterface $conditions a service locator of EarningConditionInterface keyed by type
     */
    public function __construct(
        private readonly ContainerInterface $conditions,
    ) {
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
        if (null === $type || !$this->conditions->has($type)) {
            return false;
        }

        $service = $this->conditions->get($type);

        return $service instanceof EarningConditionInterface &&
            $service->isSatisfied($context, $condition->getConfiguration());
    }
}
