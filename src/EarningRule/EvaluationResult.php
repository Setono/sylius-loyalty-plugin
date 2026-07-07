<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule;

/**
 * The outcome of evaluating a set of earning rules against a context.
 */
final class EvaluationResult
{
    /**
     * @param int $points the final, rounded award from live (non-dry-run) rules
     * @param array<string, mixed> $rulesBreakdown recorded on the earn transaction: rule id =>
     *        points contributed, multipliers noted
     * @param list<RuleEvaluation> $ruleEvaluations live rules, matched or not
     * @param list<RuleEvaluation> $dryRunEvaluations what each dry-run rule would have contributed
     */
    public function __construct(
        public readonly int $points,
        public readonly array $rulesBreakdown,
        public readonly array $ruleEvaluations,
        public readonly array $dryRunEvaluations,
    ) {
    }
}
