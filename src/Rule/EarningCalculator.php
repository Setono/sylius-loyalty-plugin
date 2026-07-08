<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule;

use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Rule\Amount\EarningAmountContext;
use Setono\SyliusLoyaltyPlugin\Rule\Basis\EarningBasisProviderInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * Orchestrates order earning (§3.4/§4.3): filters the applicable rules (enabled, in window, conditions
 * pass), sums the base rules — or lets the highest-priority non-stackable one win — scales the sum by
 * the applicable multipliers, and applies the program's rounding. Item claiming for taxon/product
 * scopes is layered on in a later step; this computes the order-scope award and its per-rule breakdown.
 */
final class EarningCalculator implements EarningCalculatorInterface
{
    public function __construct(
        private readonly EarningBasisProviderInterface $basisProvider,
        private readonly EarningConditionEvaluatorInterface $conditionEvaluator,
        private readonly EarningAmountEvaluatorInterface $amountEvaluator,
    ) {
    }

    public function calculate(
        OrderInterface $order,
        LoyaltyProgramInterface $program,
        iterable $rules,
        \DateTimeInterface $evaluatedAt,
    ): EarningResult {
        $context = RuleEvaluationContext::forOrder($order, $evaluatedAt);
        $amountContext = new EarningAmountContext($this->basisProvider->getBasis($order, $program));

        /** @var list<array{rule: EarningRuleInterface, points: int}> $baseRules */
        $baseRules = [];

        /** @var list<array{rule: EarningRuleInterface, factor: float}> $multiplierRules */
        $multiplierRules = [];

        foreach ($rules as $rule) {
            if (!$this->applies($rule, $context, $evaluatedAt)) {
                continue;
            }

            if (self::isMultiplier($rule)) {
                $multiplierRules[] = ['rule' => $rule, 'factor' => self::multiplierFactor($rule)];
            } else {
                $baseRules[] = ['rule' => $rule, 'points' => $this->amountEvaluator->calculate($rule, $amountContext)];
            }
        }

        [$base, $breakdown] = $this->resolveBase($baseRules);
        $multiplier = $this->resolveMultiplier($multiplierRules);

        return new EarningResult($this->round($base * $multiplier, $program->getRounding()), $breakdown, $multiplier);
    }

    private function applies(EarningRuleInterface $rule, RuleEvaluationContext $context, \DateTimeInterface $at): bool
    {
        if (!$rule->isEnabled()) {
            return false;
        }

        $startsAt = $rule->getStartsAt();
        if (null !== $startsAt && $at < $startsAt) {
            return false;
        }

        $endsAt = $rule->getEndsAt();
        if (null !== $endsAt && $at > $endsAt) {
            return false;
        }

        return $this->conditionEvaluator->matches($rule, $context);
    }

    /**
     * @param list<array{rule: EarningRuleInterface, points: int}> $baseRules
     *
     * @return array{0: int, 1: array<int, int>} the summed base points and the per-rule breakdown
     */
    private function resolveBase(array $baseRules): array
    {
        $nonStackable = array_values(array_filter($baseRules, static fn (array $r): bool => !$r['rule']->isStackable()));
        if ([] !== $nonStackable) {
            $winner = $this->highestPriority($nonStackable);
            $id = $winner['rule']->getId();

            return [$winner['points'], null === $id ? [] : [$id => $winner['points']]];
        }

        $total = 0;
        $breakdown = [];
        foreach ($baseRules as $r) {
            $total += $r['points'];
            $id = $r['rule']->getId();
            if (null !== $id) {
                $breakdown[$id] = ($breakdown[$id] ?? 0) + $r['points'];
            }
        }

        return [$total, $breakdown];
    }

    /**
     * @param list<array{rule: EarningRuleInterface, factor: float}> $multiplierRules
     */
    private function resolveMultiplier(array $multiplierRules): float
    {
        if ([] === $multiplierRules) {
            return 1.0;
        }

        $nonStackable = array_values(array_filter($multiplierRules, static fn (array $r): bool => !$r['rule']->isStackable()));
        if ([] !== $nonStackable) {
            return $this->highestPriority($nonStackable)['factor'];
        }

        $factor = 1.0;
        foreach ($multiplierRules as $r) {
            $factor *= $r['factor'];
        }

        return $factor;
    }

    /**
     * @template T of array{rule: EarningRuleInterface, ...}
     *
     * @param non-empty-list<T> $matched
     *
     * @return T
     */
    private function highestPriority(array $matched): array
    {
        usort($matched, static fn (array $a, array $b): int => $b['rule']->getPriority() <=> $a['rule']->getPriority());

        return $matched[0];
    }

    private function round(float $raw, string $rounding): int
    {
        return match ($rounding) {
            LoyaltyProgramInterface::ROUNDING_CEIL => (int) ceil($raw),
            LoyaltyProgramInterface::ROUNDING_ROUND => (int) round($raw),
            default => (int) floor($raw),
        };
    }

    private static function isMultiplier(EarningRuleInterface $rule): bool
    {
        return 'multiplier' === $rule->getAmountType();
    }

    private static function multiplierFactor(EarningRuleInterface $rule): float
    {
        $factor = $rule->getAmountConfiguration()['multiplier'] ?? 1.0;

        return is_numeric($factor) ? (float) $factor : 1.0;
    }
}
