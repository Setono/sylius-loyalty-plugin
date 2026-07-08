<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule;

use Psr\Container\ContainerInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Setono\SyliusLoyaltyPlugin\Rule\Amount\EarningAmountContext;
use Setono\SyliusLoyaltyPlugin\Rule\Amount\EarningAmountInterface;

/**
 * Resolves a rule's amountType to its base amount and computes the points it contributes. An unknown
 * (or multiplier) amount type yields 0 — multiplier rules are applied by the earning calculator, which
 * scales the summed base result rather than calling a base amount.
 */
final class EarningAmountEvaluator implements EarningAmountEvaluatorInterface
{
    /**
     * @param ContainerInterface $amounts a service locator of EarningAmountInterface keyed by type
     */
    public function __construct(
        private readonly ContainerInterface $amounts,
    ) {
    }

    public function calculate(EarningRuleInterface $rule, EarningAmountContext $context): int
    {
        $type = $rule->getAmountType();
        if (null === $type || !$this->amounts->has($type)) {
            return 0;
        }

        $amount = $this->amounts->get($type);

        return $amount instanceof EarningAmountInterface
            ? $amount->calculate($context, $rule->getAmountConfiguration())
            : 0;
    }
}
