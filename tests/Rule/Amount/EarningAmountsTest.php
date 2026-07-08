<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Rule\Amount;

use PHPUnit\Framework\TestCase;
use Setono\SyliusLoyaltyPlugin\Rule\Amount\EarningAmountContext;
use Setono\SyliusLoyaltyPlugin\Rule\Amount\FixedAmount;
use Setono\SyliusLoyaltyPlugin\Rule\Amount\PerAmount;

final class EarningAmountsTest extends TestCase
{
    /**
     * @test
     */
    public function fixed_awards_the_points_once_per_matching_unit(): void
    {
        $amount = new FixedAmount();

        self::assertSame(100, $amount->calculate(new EarningAmountContext(0, 1), ['points' => 100]));
        self::assertSame(300, $amount->calculate(new EarningAmountContext(0, 3), ['points' => 100]));
    }

    /**
     * @test
     */
    public function fixed_with_a_zero_rate_earns_nothing(): void
    {
        $amount = new FixedAmount();

        self::assertSame(0, $amount->calculate(new EarningAmountContext(5000, 4), ['points' => 0]));
        self::assertSame(0, $amount->calculate(new EarningAmountContext(5000, 4), ['points' => 'not-a-number']));
    }

    /**
     * @test
     */
    public function per_amount_awards_points_per_unit_of_basis_flooring_the_remainder(): void
    {
        $amount = new PerAmount();

        // 1 point per 1.00: basis 19.99 -> 19 (§4.3)
        self::assertSame(19, $amount->calculate(new EarningAmountContext(1999), ['points' => 1, 'per' => 100]));
        // 3 points per 1.00: basis 40.00 -> 120
        self::assertSame(120, $amount->calculate(new EarningAmountContext(4000), ['points' => 3, 'per' => 100]));
    }

    /**
     * @test
     */
    public function per_amount_guards_against_a_zero_or_missing_divisor(): void
    {
        $amount = new PerAmount();

        self::assertSame(0, $amount->calculate(new EarningAmountContext(5000), ['points' => 1, 'per' => 0]));
        self::assertSame(0, $amount->calculate(new EarningAmountContext(5000), ['points' => 1]));
    }
}
