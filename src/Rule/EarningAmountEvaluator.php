<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule;

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
    /** @var array<string, EarningAmountInterface> */
    private array $amounts = [];

    /**
     * @param iterable<EarningAmountInterface> $amounts
     */
    public function __construct(iterable $amounts)
    {
        foreach ($amounts as $amount) {
            $this->amounts[$amount->getType()] = $amount;
        }
    }

    public function calculate(EarningRuleInterface $rule, EarningAmountContext $context): int
    {
        $type = $rule->getAmountType();
        if (null === $type || !isset($this->amounts[$type])) {
            return 0;
        }

        return $this->amounts[$type]->calculate($context, $rule->getAmountConfiguration());
    }
}
