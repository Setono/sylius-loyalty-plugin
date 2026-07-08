<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Rule\Condition;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Rule\Condition\DateWindowCondition;
use Setono\SyliusLoyaltyPlugin\Rule\Condition\DayOfWeekCondition;
use Setono\SyliusLoyaltyPlugin\Rule\Condition\OrderTotalAtLeastCondition;
use Setono\SyliusLoyaltyPlugin\Rule\RuleEvaluationContext;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class EarningConditionsTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function order_total_is_satisfied_when_the_total_meets_the_threshold(): void
    {
        $condition = new OrderTotalAtLeastCondition();
        $context = $this->contextWithOrderTotal(5000);

        self::assertTrue($condition->isSatisfied($context, ['amount' => 5000]));
        self::assertTrue($condition->isSatisfied($context, ['amount' => 4999]));
        self::assertFalse($condition->isSatisfied($context, ['amount' => 5001]));
    }

    /**
     * @test
     */
    public function order_total_is_not_satisfied_without_an_order(): void
    {
        $context = new RuleEvaluationContext($this->channel(), new \DateTimeImmutable('2026-01-01'));

        self::assertFalse((new OrderTotalAtLeastCondition())->isSatisfied($context, ['amount' => 100]));
    }

    /**
     * @test
     */
    public function date_window_is_satisfied_only_inside_the_window(): void
    {
        $condition = new DateWindowCondition();
        $config = ['from' => '2026-11-27 00:00:00', 'to' => '2026-11-28 00:00:00'];

        self::assertTrue($condition->isSatisfied($this->contextAt('2026-11-27 12:00:00'), $config));
        self::assertFalse($condition->isSatisfied($this->contextAt('2026-11-26 23:59:59'), $config));
        self::assertFalse($condition->isSatisfied($this->contextAt('2026-11-28 00:00:01'), $config));
    }

    /**
     * @test
     */
    public function date_window_open_ended_bounds_are_optional(): void
    {
        $condition = new DateWindowCondition();

        self::assertTrue($condition->isSatisfied($this->contextAt('2030-01-01'), ['from' => '2026-01-01']));
        self::assertTrue($condition->isSatisfied($this->contextAt('2020-01-01'), ['to' => '2026-01-01']));
        self::assertTrue($condition->isSatisfied($this->contextAt('2026-06-01'), []));
    }

    /**
     * @test
     */
    public function date_window_accepts_bounds_already_given_as_date_objects(): void
    {
        $condition = new DateWindowCondition();
        $config = ['from' => new \DateTimeImmutable('2026-01-01'), 'to' => new \DateTimeImmutable('2026-12-31')];

        self::assertTrue($condition->isSatisfied($this->contextAt('2026-06-01'), $config));
        self::assertFalse($condition->isSatisfied($this->contextAt('2027-01-01'), $config));
    }

    /**
     * @test
     */
    public function order_total_treats_a_non_numeric_threshold_as_zero(): void
    {
        $condition = new OrderTotalAtLeastCondition();

        self::assertTrue($condition->isSatisfied($this->contextWithOrderTotal(0), ['amount' => 'not-a-number']));
    }

    /**
     * @test
     */
    public function day_of_week_matches_the_configured_iso_days(): void
    {
        $condition = new DayOfWeekCondition();
        // 2026-01-01 is a Thursday (ISO day 4)
        $thursday = $this->contextAt('2026-01-01');

        self::assertTrue($condition->isSatisfied($thursday, ['days' => [4]]));
        self::assertTrue($condition->isSatisfied($thursday, ['days' => [1, 4]]));
        self::assertFalse($condition->isSatisfied($thursday, ['days' => [5, 6]]));
        self::assertFalse($condition->isSatisfied($thursday, ['days' => []]));
    }

    private function contextWithOrderTotal(int $total): RuleEvaluationContext
    {
        $order = $this->prophesize(OrderInterface::class);
        $order->getTotal()->willReturn($total);

        return new RuleEvaluationContext($this->channel(), new \DateTimeImmutable('2026-01-01'), $order->reveal());
    }

    private function contextAt(string $evaluatedAt): RuleEvaluationContext
    {
        return new RuleEvaluationContext($this->channel(), new \DateTimeImmutable($evaluatedAt));
    }

    private function channel(): ChannelInterface
    {
        return $this->prophesize(ChannelInterface::class)->reveal();
    }
}
