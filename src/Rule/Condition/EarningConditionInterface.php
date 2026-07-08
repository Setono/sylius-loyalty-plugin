<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule\Condition;

use Setono\SyliusLoyaltyPlugin\Rule\RuleEvaluationContext;

/**
 * A condition an earning rule can carry. Projects add their own by implementing this interface and
 * tagging the service `setono_sylius_loyalty.earning_condition` (autoconfigured).
 */
interface EarningConditionInterface
{
    /**
     * The condition's type discriminator, matched against an EarningRuleCondition's type. Static so the
     * container can index the tagged conditions by type without instantiating them.
     */
    public static function getType(): string;

    /**
     * @param array<string, mixed> $configuration the condition row's configuration
     */
    public function isSatisfied(RuleEvaluationContext $context, array $configuration): bool;
}
