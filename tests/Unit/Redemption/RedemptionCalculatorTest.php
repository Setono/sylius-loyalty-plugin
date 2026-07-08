<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Redemption;

use PHPUnit\Framework\TestCase;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgram;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Redemption\RedemptionCalculator;

final class RedemptionCalculatorTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_requested_points_when_within_every_limit(): void
    {
        $program = $this->program(conversionPoints: 1, conversionAmount: 1, min: 500, maxPercent: 50);

        self::assertSame(1000, (new RedemptionCalculator())->calculate(1000, 5000, 10000, $program));
    }

    /**
     * @test
     */
    public function it_clamps_to_the_available_balance(): void
    {
        $program = $this->program(conversionPoints: 1, conversionAmount: 1, min: 500, maxPercent: 50);

        self::assertSame(700, (new RedemptionCalculator())->calculate(1000, 700, 10000, $program));
    }

    /**
     * @test
     */
    public function it_clamps_to_the_max_percentage_of_the_order(): void
    {
        // 1 point = 1 minor unit, max 50% of a 10000 order => at most 5000 points.
        $program = $this->program(conversionPoints: 1, conversionAmount: 1, min: 500, maxPercent: 50);

        self::assertSame(5000, (new RedemptionCalculator())->calculate(10000, 10000, 10000, $program));
    }

    /**
     * @test
     */
    public function it_rounds_down_to_a_clean_conversion_multiple(): void
    {
        // 100 points = 500 minor units; 1050 requested rounds down to the nearest 100.
        $program = $this->program(conversionPoints: 100, conversionAmount: 500, min: 100, maxPercent: 100);

        self::assertSame(1000, (new RedemptionCalculator())->calculate(1050, 5000, 100000, $program));
    }

    /**
     * @test
     */
    public function it_returns_zero_below_the_minimum(): void
    {
        $program = $this->program(conversionPoints: 1, conversionAmount: 1, min: 500, maxPercent: 100);

        self::assertSame(0, (new RedemptionCalculator())->calculate(400, 5000, 100000, $program));
    }

    /**
     * @test
     */
    public function it_returns_zero_when_redemption_is_misconfigured(): void
    {
        $program = $this->program(conversionPoints: 0, conversionAmount: 1, min: 0, maxPercent: 100);

        self::assertSame(0, (new RedemptionCalculator())->calculate(1000, 5000, 100000, $program));
    }

    /**
     * @test
     */
    public function it_converts_applied_points_to_a_money_amount(): void
    {
        $program = $this->program(conversionPoints: 100, conversionAmount: 500, min: 100, maxPercent: 100);

        self::assertSame(5000, (new RedemptionCalculator())->amount(1000, $program));
    }

    private function program(int $conversionPoints, int $conversionAmount, int $min, int $maxPercent): LoyaltyProgramInterface
    {
        $program = new LoyaltyProgram();
        $program->setRedemptionConversionPoints($conversionPoints);
        $program->setRedemptionConversionAmount($conversionAmount);
        $program->setMinRedeemPoints($min);
        $program->setMaxRedeemPercentOfOrder($maxPercent);

        return $program;
    }
}
