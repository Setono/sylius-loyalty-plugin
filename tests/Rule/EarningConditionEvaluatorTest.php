<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Rule;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Model\EarningRule;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleCondition;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Setono\SyliusLoyaltyPlugin\Rule\Condition\EarningConditionInterface;
use Setono\SyliusLoyaltyPlugin\Rule\EarningConditionEvaluator;
use Setono\SyliusLoyaltyPlugin\Rule\RuleEvaluationContext;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class EarningConditionEvaluatorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function a_rule_without_conditions_always_matches(): void
    {
        $evaluator = $this->evaluator([]);

        self::assertTrue($evaluator->matches($this->rule(EarningRuleInterface::CONDITIONS_MATCH_ALL), $this->context()));
        self::assertTrue($evaluator->matches($this->rule(EarningRuleInterface::CONDITIONS_MATCH_ANY), $this->context()));
    }

    /**
     * @test
     */
    public function all_requires_every_condition_to_pass(): void
    {
        $evaluator = $this->evaluator(['pass' => true, 'fail' => false]);

        self::assertTrue($evaluator->matches($this->rule(EarningRuleInterface::CONDITIONS_MATCH_ALL, 'pass'), $this->context()));
        self::assertFalse($evaluator->matches($this->rule(EarningRuleInterface::CONDITIONS_MATCH_ALL, 'pass', 'fail'), $this->context()));
    }

    /**
     * @test
     */
    public function any_requires_at_least_one_condition_to_pass(): void
    {
        $evaluator = $this->evaluator(['pass' => true, 'fail' => false]);

        self::assertTrue($evaluator->matches($this->rule(EarningRuleInterface::CONDITIONS_MATCH_ANY, 'fail', 'pass'), $this->context()));
        self::assertFalse($evaluator->matches($this->rule(EarningRuleInterface::CONDITIONS_MATCH_ANY, 'fail'), $this->context()));
    }

    /**
     * @test
     */
    public function an_unknown_condition_type_fails_closed(): void
    {
        $evaluator = $this->evaluator(['pass' => true]);

        self::assertFalse($evaluator->matches($this->rule(EarningRuleInterface::CONDITIONS_MATCH_ALL, 'pass', 'unregistered'), $this->context()));
        self::assertFalse($evaluator->matches($this->rule(EarningRuleInterface::CONDITIONS_MATCH_ANY, 'unregistered'), $this->context()));
    }

    /**
     * @test
     */
    public function a_locator_entry_that_is_not_a_condition_fails_closed(): void
    {
        $evaluator = new EarningConditionEvaluator(new ServiceLocator([
            'weird' => static fn (): object => new \stdClass(),
        ]));

        self::assertFalse($evaluator->matches($this->rule(EarningRuleInterface::CONDITIONS_MATCH_ALL, 'weird'), $this->context()));
    }

    /**
     * @param array<string, bool> $satisfiedByType
     */
    private function evaluator(array $satisfiedByType): EarningConditionEvaluator
    {
        $factories = [];
        foreach ($satisfiedByType as $type => $satisfied) {
            $factories[$type] = fn (): EarningConditionInterface => $this->condition($satisfied);
        }

        return new EarningConditionEvaluator(new ServiceLocator($factories));
    }

    private function rule(string $conditionsMatch, string ...$conditionTypes): EarningRule
    {
        $rule = new EarningRule();
        $rule->setConditionsMatch($conditionsMatch);

        foreach ($conditionTypes as $type) {
            $condition = new EarningRuleCondition();
            $condition->setType($type);
            $rule->addCondition($condition);
        }

        return $rule;
    }

    private function condition(bool $satisfied): EarningConditionInterface
    {
        return new class($satisfied) implements EarningConditionInterface {
            public function __construct(
                private readonly bool $satisfied,
            ) {
            }

            public static function getType(): string
            {
                return 'stub';
            }

            public function isSatisfied(RuleEvaluationContext $context, array $configuration): bool
            {
                return $this->satisfied;
            }
        };
    }

    private function context(): RuleEvaluationContext
    {
        return new RuleEvaluationContext($this->prophesize(ChannelInterface::class)->reveal(), new \DateTimeImmutable('2026-01-01'));
    }
}
