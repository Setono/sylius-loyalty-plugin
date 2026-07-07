<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule;

use Psr\Log\LoggerInterface;
use Setono\SyliusLoyaltyPlugin\EarningRule\Amount\AmountCalculationInput;
use Setono\SyliusLoyaltyPlugin\EarningRule\Amount\AmountCalculatorRegistryInterface;
use Setono\SyliusLoyaltyPlugin\EarningRule\Amount\MultiplierAmountCalculator;
use Setono\SyliusLoyaltyPlugin\EarningRule\Checker\ConditionCheckerRegistryInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductInterface;

final class EarningRuleEvaluator implements EarningRuleEvaluatorInterface
{
    public function __construct(
        private readonly ConditionCheckerRegistryInterface $conditionCheckers,
        private readonly AmountCalculatorRegistryInterface $amountCalculators,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function evaluate(iterable $rules, EarningContext $context, LoyaltyProgramInterface $program): EvaluationResult
    {
        $active = [];
        foreach ($rules as $rule) {
            if ($rule->isEnabled() && $this->isInWindow($rule, $context)) {
                $active[] = $rule;
            }
        }

        $live = array_values(array_filter($active, static fn (EarningRuleInterface $rule): bool => !$rule->isDryRun()));

        [$total, $evaluations] = $this->run($live, $context);

        // Each dry-run rule is evaluated as if it were live — alongside the actual live rules,
        // so claiming and stacking behave exactly as they would — but its effect never reaches
        // the live result.
        $dryRunEvaluations = [];
        foreach ($active as $rule) {
            if (!$rule->isDryRun()) {
                continue;
            }

            [$dryTotal, $dryEvaluations] = $this->run([...$live, $rule], $context);

            foreach ($dryEvaluations as $evaluation) {
                if ($evaluation->rule === $rule) {
                    // For multiplier rules the contribution is the delta they cause on the total
                    if (null !== $evaluation->factor && $evaluation->applied) {
                        $evaluation = new RuleEvaluation(
                            $rule,
                            $evaluation->matched,
                            $evaluation->failedConditions,
                            $evaluation->claimedItems,
                            $evaluation->claimedBasis,
                            $evaluation->claimedUnits,
                            $dryTotal - $total,
                            $evaluation->factor,
                            $evaluation->applied,
                        );
                    }

                    $dryRunEvaluations[] = $evaluation;
                }
            }
        }

        // The tier's earning multiplier applies after all rules, immediately before rounding
        $tierMultiplier = $context->account?->getTier()?->getEarningMultiplier() ?? 1.0;

        return new EvaluationResult(
            $this->round($total * $tierMultiplier, $program),
            $this->buildBreakdown($evaluations),
            $evaluations,
            $dryRunEvaluations,
        );
    }

    /**
     * Runs a full evaluation of the given rules and returns the unrounded total and the
     * per-rule detail.
     *
     * @param list<EarningRuleInterface> $rules
     *
     * @return array{0: float, 1: list<RuleEvaluation>}
     */
    private function run(array $rules, EarningContext $context): array
    {
        $matched = [];
        $failedConditions = [];
        foreach ($rules as $key => $rule) {
            $failedConditions[$key] = $this->failedConditions($rule, $context);
            $matched[$key] = $this->conditionsPass($rule, $failedConditions[$key]);
        }

        $baseRules = [];
        $multiplierRules = [];
        foreach ($rules as $key => $rule) {
            if (MultiplierAmountCalculator::TYPE === $rule->getAmountType()) {
                $multiplierRules[$key] = $rule;
            } else {
                $baseRules[$key] = $rule;
            }
        }

        // Claiming: every item is claimed at the most specific matching scope; the competitor
        // set at that scope is resolved by stackability. Unclaimed items form the remainder of
        // the order-scoped rules.
        /** @var array<int, array<int, int>> $claimedItems rule key => item id => amount */
        $claimedItems = [];

        /** @var array<int, int> $claimedUnits rule key => units */
        $claimedUnits = [];

        $remainderBasis = $context->extraAmount;

        $items = $this->itemsById($context);
        foreach ($context->itemAmounts as $itemId => $amount) {
            $item = $items[$itemId] ?? null;

            $competitors = $this->itemCompetitors($baseRules, $matched, $item);
            if ([] === $competitors) {
                $remainderBasis += $amount;

                continue;
            }

            foreach ($this->resolveStacking($competitors) as $key => $rule) {
                $claimedItems[$key][$itemId] = $amount;
                $claimedUnits[$key] = ($claimedUnits[$key] ?? 0) + ($item?->getQuantity() ?? 1);
            }
        }

        // Order-scoped rules compete for the remainder (or for the whole basis in orderless
        // action-trigger contexts)
        $orderScopedMatched = array_filter(
            $baseRules,
            static fn (EarningRuleInterface $rule, int $key): bool => $matched[$key] && EarningRuleInterface::SCOPE_ORDER === $rule->getScope(),
            \ARRAY_FILTER_USE_BOTH,
        );

        $orderScopedApplied = $this->resolveStacking($orderScopedMatched);

        // Base points
        $baseTotal = 0.0;
        $points = [];
        $applied = [];
        foreach ($baseRules as $key => $rule) {
            $isOrderScoped = EarningRuleInterface::SCOPE_ORDER === $rule->getScope();
            $applied[$key] = $matched[$key] && ($isOrderScoped ? isset($orderScopedApplied[$key]) : isset($claimedItems[$key]));

            if (!$applied[$key]) {
                $points[$key] = 0.0;

                continue;
            }

            $input = new AmountCalculationInput(
                $isOrderScoped ? $remainderBasis : (int) array_sum($claimedItems[$key] ?? []),
                $isOrderScoped ? 1 : ($claimedUnits[$key] ?? 1),
                $context,
            );

            $points[$key] = $this->calculate($rule, $input);
            $baseTotal += $points[$key];
        }

        // Multipliers: stackable ones multiply cumulatively; any non-stackable wins alone
        $matchedMultipliers = array_filter(
            $multiplierRules,
            static fn (int $key): bool => $matched[$key],
            \ARRAY_FILTER_USE_KEY,
        );

        $appliedMultipliers = $this->resolveStacking($matchedMultipliers);

        $factor = 1.0;
        $factors = [];
        foreach ($multiplierRules as $key => $rule) {
            $applied[$key] = isset($appliedMultipliers[$key]);
            $factors[$key] = MultiplierAmountCalculator::factor($rule->getAmountConfiguration());
            if ($applied[$key]) {
                $factor *= $factors[$key];
            }
        }

        $evaluations = [];
        foreach ($rules as $key => $rule) {
            $isMultiplier = MultiplierAmountCalculator::TYPE === $rule->getAmountType();

            $evaluations[] = new RuleEvaluation(
                $rule,
                $matched[$key],
                $failedConditions[$key],
                $claimedItems[$key] ?? [],
                EarningRuleInterface::SCOPE_ORDER === $rule->getScope() ? $remainderBasis : (int) array_sum($claimedItems[$key] ?? []),
                $claimedUnits[$key] ?? 0,
                $points[$key] ?? 0.0,
                $isMultiplier ? $factors[$key] : null,
                $applied[$key] ?? false,
            );
        }

        return [$baseTotal * $factor, $evaluations];
    }

    /**
     * The matched base rules competing for the given item, taken from the most specific
     * matching scope (product > taxon).
     *
     * @param array<int, EarningRuleInterface> $baseRules
     * @param array<int, bool> $matched
     *
     * @return array<int, EarningRuleInterface>
     */
    private function itemCompetitors(array $baseRules, array $matched, ?OrderItemInterface $item): array
    {
        if (null === $item) {
            return [];
        }

        foreach ([EarningRuleInterface::SCOPE_PRODUCT, EarningRuleInterface::SCOPE_TAXON] as $scope) {
            $competitors = array_filter(
                $baseRules,
                fn (EarningRuleInterface $rule, int $key): bool => $matched[$key] &&
                    $rule->getScope() === $scope &&
                    $this->scopeMatchesItem($rule, $item),
                \ARRAY_FILTER_USE_BOTH,
            );

            if ([] !== $competitors) {
                return $competitors;
            }
        }

        return [];
    }

    private function scopeMatchesItem(EarningRuleInterface $rule, OrderItemInterface $item): bool
    {
        $product = $item->getProduct();
        if (!$product instanceof ProductInterface) {
            return false;
        }

        $configuration = $rule->getScopeConfiguration();

        if (EarningRuleInterface::SCOPE_PRODUCT === $rule->getScope()) {
            /** @var list<string> $products */
            $products = array_values(array_filter((array) ($configuration['products'] ?? []), is_string(...)));

            return in_array($product->getCode(), $products, true);
        }

        if (EarningRuleInterface::SCOPE_TAXON === $rule->getScope()) {
            /** @var list<string> $taxons */
            $taxons = array_values(array_filter((array) ($configuration['taxons'] ?? []), is_string(...)));

            foreach ($product->getTaxons() as $taxon) {
                if (in_array($taxon->getCode(), $taxons, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * All stackable rules apply and sum; if any rule is non-stackable, the highest-priority
     * (then lowest id) non-stackable rule applies alone.
     *
     * @param array<int, EarningRuleInterface> $competitors
     *
     * @return array<int, EarningRuleInterface> the applied subset, keyed as the input
     */
    private function resolveStacking(array $competitors): array
    {
        $nonStackable = array_filter(
            $competitors,
            static fn (EarningRuleInterface $rule): bool => !$rule->isStackable(),
        );

        if ([] === $nonStackable) {
            return $competitors;
        }

        uasort(
            $nonStackable,
            static fn (EarningRuleInterface $a, EarningRuleInterface $b): int => [-$a->getPriority(), $a->getId() ?? \PHP_INT_MAX]
                <=> [-$b->getPriority(), $b->getId() ?? \PHP_INT_MAX],
        );

        $winnerKey = array_key_first($nonStackable);

        return [$winnerKey => $nonStackable[$winnerKey]];
    }

    /**
     * @return list<string> the condition types that failed
     */
    private function failedConditions(EarningRuleInterface $rule, EarningContext $context): array
    {
        $failed = [];
        foreach ($rule->getConditions() as $condition) {
            $type = $condition->getType();
            if (null === $type) {
                continue;
            }

            $checker = $this->conditionCheckers->get($type);
            if (null === $checker) {
                $this->logger->warning(sprintf(
                    '[Loyalty] Unknown condition type "%s" on earning rule (id: %s); the condition fails',
                    $type,
                    (string) ($rule->getId() ?? 'unsaved'),
                ));
                $failed[] = $type;

                continue;
            }

            if (!$checker->check($condition->getConfiguration(), $context)) {
                $failed[] = $type;
            }
        }

        return $failed;
    }

    /**
     * @param list<string> $failedConditions
     */
    private function conditionsPass(EarningRuleInterface $rule, array $failedConditions): bool
    {
        $conditionCount = $rule->getConditions()->count();
        if (0 === $conditionCount) {
            return true;
        }

        if (EarningRuleInterface::CONDITIONS_MATCH_ANY === $rule->getConditionsMatch()) {
            return count($failedConditions) < $conditionCount;
        }

        return [] === $failedConditions;
    }

    private function calculate(EarningRuleInterface $rule, AmountCalculationInput $input): float
    {
        $amountType = $rule->getAmountType();
        if (null === $amountType) {
            return 0.0;
        }

        $calculator = $this->amountCalculators->get($amountType);
        if (null === $calculator) {
            $this->logger->warning(sprintf(
                '[Loyalty] Unknown amount type "%s" on earning rule (id: %s); the rule contributes nothing',
                $amountType,
                (string) ($rule->getId() ?? 'unsaved'),
            ));

            return 0.0;
        }

        return $calculator->calculate($rule->getAmountConfiguration(), $input);
    }

    private function isInWindow(EarningRuleInterface $rule, EarningContext $context): bool
    {
        $now = $context->getNow();

        $startsAt = $rule->getStartsAt();
        if (null !== $startsAt && $now < $startsAt) {
            return false;
        }

        $endsAt = $rule->getEndsAt();

        return null === $endsAt || $now <= $endsAt;
    }

    private function round(float $points, LoyaltyProgramInterface $program): int
    {
        return match ($program->getRounding()) {
            LoyaltyProgramInterface::ROUNDING_CEIL => (int) ceil($points),
            LoyaltyProgramInterface::ROUNDING_ROUND => (int) round($points),
            default => (int) floor($points),
        };
    }

    /**
     * @return array<int, OrderItemInterface>
     */
    private function itemsById(EarningContext $context): array
    {
        $items = [];
        if (null !== $context->order) {
            foreach ($context->order->getItems() as $item) {
                if ($item instanceof OrderItemInterface && null !== $item->getId()) {
                    $items[(int) $item->getId()] = $item;
                }
            }
        }

        return $items;
    }

    /**
     * @param list<RuleEvaluation> $evaluations
     *
     * @return array<string, mixed>
     */
    private function buildBreakdown(array $evaluations): array
    {
        $rules = [];
        $multipliers = [];
        foreach ($evaluations as $evaluation) {
            if (!$evaluation->applied) {
                continue;
            }

            $id = (string) ($evaluation->rule->getId() ?? spl_object_id($evaluation->rule));
            if (null !== $evaluation->factor) {
                $multipliers[$id] = $evaluation->factor;
            } else {
                $rules[$id] = round($evaluation->points, 2);
            }
        }

        return [
            'rules' => $rules,
            'multipliers' => $multipliers,
        ];
    }
}
