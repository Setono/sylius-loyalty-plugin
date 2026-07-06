<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\EarningRule;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\NullLogger;
use Setono\SyliusLoyaltyPlugin\EarningRule\Amount\AmountCalculatorRegistry;
use Setono\SyliusLoyaltyPlugin\EarningRule\Amount\FixedAmountCalculator;
use Setono\SyliusLoyaltyPlugin\EarningRule\Amount\MultiplierAmountCalculator;
use Setono\SyliusLoyaltyPlugin\EarningRule\Amount\PerAmountCalculator;
use Setono\SyliusLoyaltyPlugin\EarningRule\Checker\ConditionCheckerRegistry;
use Setono\SyliusLoyaltyPlugin\EarningRule\Checker\DayOfWeekConditionChecker;
use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;
use Setono\SyliusLoyaltyPlugin\EarningRule\EarningRuleEvaluator;
use Setono\SyliusLoyaltyPlugin\Model\EarningRule;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleCondition;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgram;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductInterface;

final class EarningRuleEvaluatorTest extends TestCase
{
    use ProphecyTrait;

    private LoyaltyProgram $program;

    protected function setUp(): void
    {
        $this->program = new LoyaltyProgram();
    }

    /**
     * @test
     */
    public function it_awards_per_amount_on_the_order_basis(): void
    {
        // Given a paid order of 100.00 with rule "1 pt / 1.00" the award is 100 points
        $rule = self::perAmountRule(1, points: 1, perAmount: 100);
        $context = $this->orderContext([1 => 10000]);

        $result = $this->evaluator()->evaluate([$rule], $context, $this->program);

        self::assertSame(100, $result->points);
    }

    /**
     * @test
     */
    public function it_floors_the_award_by_default(): void
    {
        // Given rounding = floor and basis 19.99 with 1pt/1.00, the award is 19
        $rule = self::perAmountRule(1, points: 1, perAmount: 100);
        $context = $this->orderContext([1 => 1999]);

        $result = $this->evaluator()->evaluate([$rule], $context, $this->program);

        self::assertSame(19, $result->points);
    }

    /**
     * @test
     */
    public function it_claims_items_exclusively_with_product_scope_beating_order_scope(): void
    {
        // Given order rule 1pt/1.00 and a product-scoped rule 3pt/1.00 for product X: an order
        // with 40.00 of X and 60.00 of other items awards 3*40 + 1*60 = 180 points
        $orderRule = self::perAmountRule(1, points: 1, perAmount: 100);
        $productRule = self::perAmountRule(2, points: 3, perAmount: 100);
        $productRule->setScope(EarningRuleInterface::SCOPE_PRODUCT);
        $productRule->setScopeConfiguration(['products' => ['X']]);

        $context = $this->orderContext([1 => 4000, 2 => 6000], [1 => 'X', 2 => 'Y']);

        $result = $this->evaluator()->evaluate([$orderRule, $productRule], $context, $this->program);

        self::assertSame(180, $result->points);
    }

    /**
     * @test
     */
    public function it_excludes_a_product_from_earning_via_a_zero_rate_scoped_rule(): void
    {
        $orderRule = self::perAmountRule(1, points: 1, perAmount: 100);
        $zeroRule = self::perAmountRule(2, points: 0, perAmount: 100);
        $zeroRule->setScope(EarningRuleInterface::SCOPE_PRODUCT);
        $zeroRule->setScopeConfiguration(['products' => ['GIFT_CARD']]);

        $context = $this->orderContext([1 => 5000, 2 => 5000], [1 => 'GIFT_CARD', 2 => 'Y']);

        $result = $this->evaluator()->evaluate([$orderRule, $zeroRule], $context, $this->program);

        self::assertSame(50, $result->points);
    }

    /**
     * @test
     */
    public function it_applies_the_highest_priority_non_stackable_rule_alone(): void
    {
        $stackable = self::perAmountRule(1, points: 1, perAmount: 100);
        $nonStackableLow = self::perAmountRule(2, points: 2, perAmount: 100);
        $nonStackableLow->setStackable(false);
        $nonStackableHigh = self::perAmountRule(3, points: 5, perAmount: 100);
        $nonStackableHigh->setStackable(false);
        $nonStackableHigh->setPriority(10);

        $context = $this->orderContext([1 => 10000]);

        $result = $this->evaluator()->evaluate([$stackable, $nonStackableLow, $nonStackableHigh], $context, $this->program);

        self::assertSame(500, $result->points);
    }

    /**
     * @test
     */
    public function it_sums_stackable_rules_competing_for_the_same_basis(): void
    {
        $first = self::perAmountRule(1, points: 1, perAmount: 100);
        $second = self::perAmountRule(2, points: 2, perAmount: 100);

        $context = $this->orderContext([1 => 10000]);

        $result = $this->evaluator()->evaluate([$first, $second], $context, $this->program);

        self::assertSame(300, $result->points);
    }

    /**
     * @test
     */
    public function it_applies_stackable_multipliers_cumulatively(): void
    {
        $base = self::perAmountRule(1, points: 1, perAmount: 100);
        $double = self::multiplierRule(2, 2.0);
        $triple = self::multiplierRule(3, 3.0);

        $context = $this->orderContext([1 => 10000]);

        $result = $this->evaluator()->evaluate([$base, $double, $triple], $context, $this->program);

        self::assertSame(600, $result->points);
    }

    /**
     * @test
     */
    public function it_applies_a_single_non_stackable_multiplier(): void
    {
        $base = self::perAmountRule(1, points: 1, perAmount: 100);
        $stackable = self::multiplierRule(2, 2.0);
        $nonStackable = self::multiplierRule(3, 5.0);
        $nonStackable->setStackable(false);
        $nonStackable->setPriority(10);

        $context = $this->orderContext([1 => 10000]);

        $result = $this->evaluator()->evaluate([$base, $stackable, $nonStackable], $context, $this->program);

        self::assertSame(500, $result->points);
    }

    /**
     * @test
     */
    public function it_matches_any_condition_when_configured(): void
    {
        $rule = self::perAmountRule(1, points: 1, perAmount: 100);
        $rule->setConditionsMatch(EarningRuleInterface::CONDITIONS_MATCH_ANY);
        $rule->addCondition(self::condition(DayOfWeekConditionChecker::TYPE, ['days' => [(int) (new \DateTimeImmutable('2026-07-06'))->format('N')]]));
        $rule->addCondition(self::condition(DayOfWeekConditionChecker::TYPE, ['days' => [7]]));

        $context = $this->orderContext([1 => 10000], now: new \DateTimeImmutable('2026-07-06 12:00'));

        $result = $this->evaluator()->evaluate([$rule], $context, $this->program);

        self::assertSame(100, $result->points);
    }

    /**
     * @test
     */
    public function it_requires_all_conditions_by_default(): void
    {
        $rule = self::perAmountRule(1, points: 1, perAmount: 100);
        $rule->addCondition(self::condition(DayOfWeekConditionChecker::TYPE, ['days' => [(int) (new \DateTimeImmutable('2026-07-06'))->format('N')]]));
        $rule->addCondition(self::condition(DayOfWeekConditionChecker::TYPE, ['days' => [7]]));

        $context = $this->orderContext([1 => 10000], now: new \DateTimeImmutable('2026-07-06 12:00'));

        $result = $this->evaluator()->evaluate([$rule], $context, $this->program);

        self::assertSame(0, $result->points);
        self::assertFalse($result->ruleEvaluations[0]->matched);
    }

    /**
     * @test
     */
    public function it_diverts_dry_run_rules_from_the_live_result(): void
    {
        $live = self::perAmountRule(1, points: 1, perAmount: 100);
        $dryRun = self::perAmountRule(2, points: 10, perAmount: 100);
        $dryRun->setDryRun(true);

        $context = $this->orderContext([1 => 10000]);

        $result = $this->evaluator()->evaluate([$live, $dryRun], $context, $this->program);

        self::assertSame(100, $result->points);
        self::assertCount(1, $result->dryRunEvaluations);
        self::assertSame(1000.0, $result->dryRunEvaluations[0]->points);
    }

    /**
     * @test
     */
    public function it_ignores_rules_outside_their_window(): void
    {
        $rule = self::perAmountRule(1, points: 1, perAmount: 100);
        $rule->setStartsAt(new \DateTimeImmutable('2026-11-27'));

        $context = $this->orderContext([1 => 10000], now: new \DateTimeImmutable('2026-07-06'));

        $result = $this->evaluator()->evaluate([$rule], $context, $this->program);

        self::assertSame(0, $result->points);
        self::assertSame([], $result->ruleEvaluations);
    }

    /**
     * @test
     */
    public function it_awards_fixed_points_per_matching_unit_under_item_scopes(): void
    {
        $rule = self::rule(1, FixedAmountCalculator::TYPE, ['points' => 10]);
        $rule->setScope(EarningRuleInterface::SCOPE_PRODUCT);
        $rule->setScopeConfiguration(['products' => ['X']]);

        // Item 1 has quantity 3
        $context = $this->orderContext([1 => 6000], [1 => 'X'], quantities: [1 => 3]);

        $result = $this->evaluator()->evaluate([$rule], $context, $this->program);

        self::assertSame(30, $result->points);
    }

    private function evaluator(): EarningRuleEvaluator
    {
        $conditionCheckers = new ConditionCheckerRegistry();
        $conditionCheckers->add(new DayOfWeekConditionChecker());

        $amountCalculators = new AmountCalculatorRegistry();
        $amountCalculators->add(new FixedAmountCalculator());
        $amountCalculators->add(new PerAmountCalculator());
        $amountCalculators->add(new MultiplierAmountCalculator());

        return new EarningRuleEvaluator($conditionCheckers, $amountCalculators, new NullLogger());
    }

    /**
     * @param array<int, int> $itemAmounts
     * @param array<int, string> $productCodes item id => product code
     * @param array<int, int> $quantities item id => quantity
     */
    private function orderContext(
        array $itemAmounts,
        array $productCodes = [],
        ?\DateTimeImmutable $now = null,
        array $quantities = [],
    ): EarningContext {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();

        $items = [];
        foreach (array_keys($itemAmounts) as $itemId) {
            $product = $this->prophesize(ProductInterface::class);
            $product->getCode()->willReturn($productCodes[$itemId] ?? sprintf('PRODUCT_%d', $itemId));
            $product->getTaxons()->willReturn(new ArrayCollection());

            $item = $this->prophesize(OrderItemInterface::class);
            $item->getId()->willReturn($itemId);
            $item->getProduct()->willReturn($product->reveal());
            $item->getQuantity()->willReturn($quantities[$itemId] ?? 1);

            $items[] = $item->reveal();
        }

        $order = $this->prophesize(OrderInterface::class);
        $order->getItems()->willReturn(new ArrayCollection($items));

        return new EarningContext(
            channel: $channel,
            order: $order->reveal(),
            itemAmounts: $itemAmounts,
            now: $now,
        );
    }

    /**
     * @param array<string, mixed> $amountConfiguration
     */
    private static function rule(int $id, string $amountType, array $amountConfiguration): EarningRule
    {
        $rule = new EarningRule();
        $rule->setAmountType($amountType);
        $rule->setAmountConfiguration($amountConfiguration);

        $reflection = new \ReflectionProperty(EarningRule::class, 'id');
        $reflection->setValue($rule, $id);

        return $rule;
    }

    private static function perAmountRule(int $id, int $points, int $perAmount): EarningRule
    {
        return self::rule($id, PerAmountCalculator::TYPE, ['points' => $points, 'per_amount' => $perAmount]);
    }

    private static function multiplierRule(int $id, float $factor): EarningRule
    {
        return self::rule($id, MultiplierAmountCalculator::TYPE, ['factor' => $factor]);
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private static function condition(string $type, array $configuration): EarningRuleCondition
    {
        $condition = new EarningRuleCondition();
        $condition->setType($type);
        $condition->setConfiguration($configuration);

        return $condition;
    }
}
