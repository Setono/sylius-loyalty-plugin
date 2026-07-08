<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Rule;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Model\EarningRule;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Rule\Amount\FixedAmount;
use Setono\SyliusLoyaltyPlugin\Rule\Amount\PerAmount;
use Setono\SyliusLoyaltyPlugin\Rule\Basis\EarningBasisProviderInterface;
use Setono\SyliusLoyaltyPlugin\Rule\EarningAmountEvaluator;
use Setono\SyliusLoyaltyPlugin\Rule\EarningCalculator;
use Setono\SyliusLoyaltyPlugin\Rule\EarningConditionEvaluator;
use Setono\SyliusLoyaltyPlugin\Rule\EarningResult;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class EarningCalculatorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_sums_stackable_base_rules(): void
    {
        // basis 19.99: 1pt/1.00 -> 19, 1pt/2.00 -> 9, summed -> 28
        $result = $this->calculate(1999, [
            $this->rule('per_amount', ['points' => 1, 'per' => 100]),
            $this->rule('per_amount', ['points' => 1, 'per' => 200]),
        ]);

        self::assertSame(28, $result->points);
    }

    /**
     * @test
     */
    public function a_non_stackable_base_rule_applies_alone(): void
    {
        // a non-stackable fixed 100 suppresses the stackable per_amount
        $result = $this->calculate(1999, [
            $this->rule('per_amount', ['points' => 1, 'per' => 100]),
            $this->rule('fixed', ['points' => 100], stackable: false, priority: 10),
        ]);

        self::assertSame(100, $result->points);
    }

    /**
     * @test
     */
    public function the_highest_priority_non_stackable_rule_wins(): void
    {
        $result = $this->calculate(1999, [
            $this->rule('fixed', ['points' => 50], stackable: false, priority: 5),
            $this->rule('fixed', ['points' => 200], stackable: false, priority: 20),
        ]);

        self::assertSame(200, $result->points);
    }

    /**
     * @test
     */
    public function multipliers_scale_the_summed_base(): void
    {
        $result = $this->calculate(1999, [
            $this->rule('per_amount', ['points' => 1, 'per' => 100]),   // 19
            $this->rule('multiplier', ['multiplier' => 2.0]),
        ]);

        self::assertSame(38, $result->points);
        self::assertSame(2.0, $result->multiplier);
    }

    /**
     * @test
     */
    public function rounding_is_applied_to_the_multiplied_total(): void
    {
        $base = [$this->rule('per_amount', ['points' => 1, 'per' => 100])]; // 19
        $multiplier = $this->rule('multiplier', ['multiplier' => 1.5]);     // 28.5

        self::assertSame(28, $this->calculate(1999, [...$base, $multiplier], LoyaltyProgramInterface::ROUNDING_FLOOR)->points);
        self::assertSame(29, $this->calculate(1999, [...$base, $multiplier], LoyaltyProgramInterface::ROUNDING_ROUND)->points);
        self::assertSame(29, $this->calculate(1999, [...$base, $multiplier], LoyaltyProgramInterface::ROUNDING_CEIL)->points);
    }

    /**
     * @test
     */
    public function disabled_or_out_of_window_rules_are_skipped(): void
    {
        $disabled = $this->rule('per_amount', ['points' => 1, 'per' => 100]);
        $disabled->setEnabled(false);

        $future = $this->rule('per_amount', ['points' => 1, 'per' => 100]);
        $future->setStartsAt(new \DateTimeImmutable('2099-01-01'));

        self::assertSame(0, $this->calculate(1999, [$disabled, $future])->points);
    }

    /**
     * @test
     */
    public function the_breakdown_maps_rule_ids_to_their_base_points(): void
    {
        $result = $this->calculate(1999, [
            $this->rule('per_amount', ['points' => 1, 'per' => 100], id: 7),  // 19
            $this->rule('fixed', ['points' => 5], id: 9),                      // 5
        ]);

        self::assertSame([7 => 19, 9 => 5], $result->breakdown);
    }

    /**
     * @param list<EarningRule> $rules
     */
    private function calculate(int $basis, array $rules, string $rounding = LoyaltyProgramInterface::ROUNDING_FLOOR): EarningResult
    {
        $basisProvider = new class($basis) implements EarningBasisProviderInterface {
            public function __construct(private readonly int $basis)
            {
            }

            public function getBasis(OrderInterface $order, LoyaltyProgramInterface $program): int
            {
                return $this->basis;
            }
        };

        $calculator = new EarningCalculator(
            $basisProvider,
            new EarningConditionEvaluator(new ServiceLocator([])),
            new EarningAmountEvaluator(new ServiceLocator([
                'fixed' => static fn (): FixedAmount => new FixedAmount(),
                'per_amount' => static fn (): PerAmount => new PerAmount(),
            ])),
        );

        $order = $this->prophesize(OrderInterface::class);
        $order->getChannel()->willReturn($this->prophesize(ChannelInterface::class)->reveal());
        $order->getCustomer()->willReturn(null);

        $program = $this->prophesize(LoyaltyProgramInterface::class);
        $program->getRounding()->willReturn($rounding);

        return $calculator->calculate($order->reveal(), $program->reveal(), $rules, new \DateTimeImmutable('2026-06-01'));
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function rule(string $amountType, array $configuration, bool $stackable = true, int $priority = 0, ?int $id = null): EarningRule
    {
        $rule = new EarningRule();
        $rule->setEnabled(true);
        $rule->setAmountType($amountType);
        $rule->setAmountConfiguration($configuration);
        $rule->setStackable($stackable);
        $rule->setPriority($priority);

        if (null !== $id) {
            (new \ReflectionProperty(EarningRule::class, 'id'))->setValue($rule, $id);
        }

        return $rule;
    }
}
