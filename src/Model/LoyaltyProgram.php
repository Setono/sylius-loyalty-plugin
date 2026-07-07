<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\ChannelInterface;

class LoyaltyProgram implements LoyaltyProgramInterface
{
    protected ?int $id = null;

    protected ?ChannelInterface $channel = null;

    protected string $awardOrderPointsAt = self::AWARD_ORDER_POINTS_AT_PAYMENT_PAID;

    protected string $earningBasis = self::EARNING_BASIS_ITEMS_TOTAL;

    protected bool $includeTaxes = false;

    protected string $rounding = self::ROUNDING_FLOOR;

    protected int $redemptionConversionPoints = 1;

    protected int $redemptionConversionAmount = 1;

    protected int $minRedeemPoints = 500;

    protected int $maxRedeemPercentOfOrder = 50;

    protected ?int $pointsExpiryDays = 365;

    protected string $clawbackPolicy = self::CLAWBACK_POLICY_ALLOW_NEGATIVE;

    protected bool $retroactiveGuestPoints = false;

    protected string $tierEvaluationWindow = self::TIER_EVALUATION_WINDOW_ROLLING_12_MONTHS;

    protected int $tierDowngradeGraceDays = 0;

    protected int $referralReferrerPoints = 500;

    protected int $referralRefereePoints = 250;

    protected int $referralMinOrderTotal = 2500;

    protected int $referralPendingExpiryDays = 180;

    protected bool $showEarnableOnProduct = true;

    protected bool $showEarnableInCart = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(?ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }

    public function getAwardOrderPointsAt(): string
    {
        return $this->awardOrderPointsAt;
    }

    public function setAwardOrderPointsAt(string $awardOrderPointsAt): void
    {
        $this->awardOrderPointsAt = $awardOrderPointsAt;
    }

    public function getEarningBasis(): string
    {
        return $this->earningBasis;
    }

    public function setEarningBasis(string $earningBasis): void
    {
        $this->earningBasis = $earningBasis;
    }

    public function includeTaxes(): bool
    {
        return $this->includeTaxes;
    }

    public function setIncludeTaxes(bool $includeTaxes): void
    {
        $this->includeTaxes = $includeTaxes;
    }

    public function getRounding(): string
    {
        return $this->rounding;
    }

    public function setRounding(string $rounding): void
    {
        $this->rounding = $rounding;
    }

    public function getRedemptionConversionPoints(): int
    {
        return $this->redemptionConversionPoints;
    }

    public function setRedemptionConversionPoints(int $redemptionConversionPoints): void
    {
        $this->redemptionConversionPoints = $redemptionConversionPoints;
    }

    public function getRedemptionConversionAmount(): int
    {
        return $this->redemptionConversionAmount;
    }

    public function setRedemptionConversionAmount(int $redemptionConversionAmount): void
    {
        $this->redemptionConversionAmount = $redemptionConversionAmount;
    }

    public function getMinRedeemPoints(): int
    {
        return $this->minRedeemPoints;
    }

    public function setMinRedeemPoints(int $minRedeemPoints): void
    {
        $this->minRedeemPoints = $minRedeemPoints;
    }

    public function getMaxRedeemPercentOfOrder(): int
    {
        return $this->maxRedeemPercentOfOrder;
    }

    public function setMaxRedeemPercentOfOrder(int $maxRedeemPercentOfOrder): void
    {
        $this->maxRedeemPercentOfOrder = $maxRedeemPercentOfOrder;
    }

    public function getPointsExpiryDays(): ?int
    {
        return $this->pointsExpiryDays;
    }

    public function setPointsExpiryDays(?int $pointsExpiryDays): void
    {
        $this->pointsExpiryDays = $pointsExpiryDays;
    }

    public function getClawbackPolicy(): string
    {
        return $this->clawbackPolicy;
    }

    public function setClawbackPolicy(string $clawbackPolicy): void
    {
        $this->clawbackPolicy = $clawbackPolicy;
    }

    public function retroactiveGuestPoints(): bool
    {
        return $this->retroactiveGuestPoints;
    }

    public function setRetroactiveGuestPoints(bool $retroactiveGuestPoints): void
    {
        $this->retroactiveGuestPoints = $retroactiveGuestPoints;
    }

    public function getTierEvaluationWindow(): string
    {
        return $this->tierEvaluationWindow;
    }

    public function setTierEvaluationWindow(string $tierEvaluationWindow): void
    {
        $this->tierEvaluationWindow = $tierEvaluationWindow;
    }

    public function getTierDowngradeGraceDays(): int
    {
        return $this->tierDowngradeGraceDays;
    }

    public function setTierDowngradeGraceDays(int $tierDowngradeGraceDays): void
    {
        $this->tierDowngradeGraceDays = $tierDowngradeGraceDays;
    }

    public function getReferralReferrerPoints(): int
    {
        return $this->referralReferrerPoints;
    }

    public function setReferralReferrerPoints(int $referralReferrerPoints): void
    {
        $this->referralReferrerPoints = $referralReferrerPoints;
    }

    public function getReferralRefereePoints(): int
    {
        return $this->referralRefereePoints;
    }

    public function setReferralRefereePoints(int $referralRefereePoints): void
    {
        $this->referralRefereePoints = $referralRefereePoints;
    }

    public function getReferralMinOrderTotal(): int
    {
        return $this->referralMinOrderTotal;
    }

    public function setReferralMinOrderTotal(int $referralMinOrderTotal): void
    {
        $this->referralMinOrderTotal = $referralMinOrderTotal;
    }

    public function getReferralPendingExpiryDays(): int
    {
        return $this->referralPendingExpiryDays;
    }

    public function setReferralPendingExpiryDays(int $referralPendingExpiryDays): void
    {
        $this->referralPendingExpiryDays = $referralPendingExpiryDays;
    }

    public function showEarnableOnProduct(): bool
    {
        return $this->showEarnableOnProduct;
    }

    public function setShowEarnableOnProduct(bool $showEarnableOnProduct): void
    {
        $this->showEarnableOnProduct = $showEarnableOnProduct;
    }

    public function showEarnableInCart(): bool
    {
        return $this->showEarnableInCart;
    }

    public function setShowEarnableInCart(bool $showEarnableInCart): void
    {
        $this->showEarnableInCart = $showEarnableInCart;
    }
}
