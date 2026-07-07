<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Model;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgram;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Sylius\Component\Core\Model\ChannelInterface;

final class LoyaltyProgramTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_has_the_documented_defaults(): void
    {
        $program = new LoyaltyProgram();

        self::assertNull($program->getId());
        self::assertNull($program->getChannel());
        self::assertSame(LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_PAYMENT_PAID, $program->getAwardOrderPointsAt());
        self::assertSame(LoyaltyProgramInterface::EARNING_BASIS_ITEMS_TOTAL, $program->getEarningBasis());
        self::assertFalse($program->includeTaxes());
        self::assertSame(LoyaltyProgramInterface::ROUNDING_FLOOR, $program->getRounding());
        self::assertSame(1, $program->getRedemptionConversionPoints());
        self::assertSame(1, $program->getRedemptionConversionAmount());
        self::assertSame(500, $program->getMinRedeemPoints());
        self::assertSame(50, $program->getMaxRedeemPercentOfOrder());
        self::assertSame(365, $program->getPointsExpiryDays());
        self::assertSame(LoyaltyProgramInterface::CLAWBACK_POLICY_ALLOW_NEGATIVE, $program->getClawbackPolicy());
        self::assertFalse($program->retroactiveGuestPoints());
        self::assertSame(LoyaltyProgramInterface::TIER_EVALUATION_WINDOW_ROLLING_12_MONTHS, $program->getTierEvaluationWindow());
        self::assertSame(0, $program->getTierDowngradeGraceDays());
        self::assertSame(500, $program->getReferralReferrerPoints());
        self::assertSame(250, $program->getReferralRefereePoints());
        self::assertSame(2500, $program->getReferralMinOrderTotal());
        self::assertSame(180, $program->getReferralPendingExpiryDays());
        self::assertTrue($program->showEarnableOnProduct());
        self::assertTrue($program->showEarnableInCart());
    }

    /**
     * @test
     */
    public function it_associates_with_a_channel(): void
    {
        $program = new LoyaltyProgram();
        $channel = $this->prophesize(ChannelInterface::class)->reveal();

        $program->setChannel($channel);

        self::assertSame($channel, $program->getChannel());
    }

    /**
     * @test
     */
    public function it_allows_disabling_point_expiry(): void
    {
        $program = new LoyaltyProgram();

        $program->setPointsExpiryDays(null);

        self::assertNull($program->getPointsExpiryDays());
    }
}
