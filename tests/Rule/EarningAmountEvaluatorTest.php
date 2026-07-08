<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Rule;

use PHPUnit\Framework\TestCase;
use Setono\SyliusLoyaltyPlugin\Model\EarningRule;
use Setono\SyliusLoyaltyPlugin\Rule\Amount\EarningAmountContext;
use Setono\SyliusLoyaltyPlugin\Rule\Amount\FixedAmount;
use Setono\SyliusLoyaltyPlugin\Rule\Amount\PerAmount;
use Setono\SyliusLoyaltyPlugin\Rule\EarningAmountEvaluator;

final class EarningAmountEvaluatorTest extends TestCase
{
    private EarningAmountEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new EarningAmountEvaluator([new FixedAmount(), new PerAmount()]);
    }

    /**
     * @test
     */
    public function it_computes_a_rules_configured_base_amount(): void
    {
        $fixed = new EarningRule();
        $fixed->setAmountType('fixed');
        $fixed->setAmountConfiguration(['points' => 50]);
        self::assertSame(50, $this->evaluator->calculate($fixed, new EarningAmountContext(1999)));

        $perAmount = new EarningRule();
        $perAmount->setAmountType('per_amount');
        $perAmount->setAmountConfiguration(['points' => 1, 'per' => 100]);
        self::assertSame(19, $this->evaluator->calculate($perAmount, new EarningAmountContext(1999)));
    }

    /**
     * @test
     */
    public function an_unset_unknown_or_multiplier_amount_type_yields_no_base_points(): void
    {
        $unset = new EarningRule();
        self::assertSame(0, $this->evaluator->calculate($unset, new EarningAmountContext(1999)));

        $multiplier = new EarningRule();
        $multiplier->setAmountType('multiplier');
        $multiplier->setAmountConfiguration(['multiplier' => 2]);
        self::assertSame(0, $this->evaluator->calculate($multiplier, new EarningAmountContext(1999)));
    }
}
