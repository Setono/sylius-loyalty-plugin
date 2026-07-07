<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * The loyalty program a channel runs: the channel's loyalty parameters.
 *
 * There is exactly one program per channel; it is created with defaults on first access.
 */
interface LoyaltyProgramInterface extends ResourceInterface
{
    /** The order-lifecycle moment order points are awarded. */
    public const AWARD_ORDER_POINTS_AT_PAYMENT_PAID = 'payment_paid';

    public const AWARD_ORDER_POINTS_AT_ORDER_FULFILLED = 'order_fulfilled';

    /** The basis order points are computed on. */
    public const EARNING_BASIS_ITEMS_TOTAL = 'items_total';

    public const EARNING_BASIS_ORDER_TOTAL = 'order_total';

    /** How fractional points are rounded. */
    public const ROUNDING_FLOOR = 'floor';

    public const ROUNDING_ROUND = 'round';

    public const ROUNDING_CEIL = 'ceil';

    /** Whether a clawback may drive the balance negative or is clamped to zero. */
    public const CLAWBACK_POLICY_ALLOW_NEGATIVE = 'allow_negative';

    public const CLAWBACK_POLICY_CLAMP_TO_ZERO = 'clamp_to_zero';

    /** The window over which tier qualification is evaluated (Phase 2). */
    public const TIER_EVALUATION_WINDOW_CALENDAR_YEAR = 'calendar_year';

    public const TIER_EVALUATION_WINDOW_ROLLING_12_MONTHS = 'rolling_12_months';

    public const TIER_EVALUATION_WINDOW_LIFETIME = 'lifetime';

    public function getId(): ?int;

    public function getChannel(): ?ChannelInterface;

    public function setChannel(?ChannelInterface $channel): void;

    public function getAwardOrderPointsAt(): string;

    public function setAwardOrderPointsAt(string $awardOrderPointsAt): void;

    public function getEarningBasis(): string;

    public function setEarningBasis(string $earningBasis): void;

    public function includeTaxes(): bool;

    public function setIncludeTaxes(bool $includeTaxes): void;

    public function getRounding(): string;

    public function setRounding(string $rounding): void;

    public function getRedemptionConversionPoints(): int;

    public function setRedemptionConversionPoints(int $redemptionConversionPoints): void;

    public function getRedemptionConversionAmount(): int;

    public function setRedemptionConversionAmount(int $redemptionConversionAmount): void;

    public function getMinRedeemPoints(): int;

    public function setMinRedeemPoints(int $minRedeemPoints): void;

    public function getMaxRedeemPercentOfOrder(): int;

    public function setMaxRedeemPercentOfOrder(int $maxRedeemPercentOfOrder): void;

    public function getPointsExpiryDays(): ?int;

    public function setPointsExpiryDays(?int $pointsExpiryDays): void;

    public function getClawbackPolicy(): string;

    public function setClawbackPolicy(string $clawbackPolicy): void;

    public function retroactiveGuestPoints(): bool;

    public function setRetroactiveGuestPoints(bool $retroactiveGuestPoints): void;

    public function getTierEvaluationWindow(): string;

    public function setTierEvaluationWindow(string $tierEvaluationWindow): void;

    public function getTierDowngradeGraceDays(): int;

    public function setTierDowngradeGraceDays(int $tierDowngradeGraceDays): void;

    public function getReferralReferrerPoints(): int;

    public function setReferralReferrerPoints(int $referralReferrerPoints): void;

    public function getReferralRefereePoints(): int;

    public function setReferralRefereePoints(int $referralRefereePoints): void;

    public function getReferralMinOrderTotal(): int;

    public function setReferralMinOrderTotal(int $referralMinOrderTotal): void;

    public function getReferralPendingExpiryDays(): int;

    public function setReferralPendingExpiryDays(int $referralPendingExpiryDays): void;

    public function showEarnableOnProduct(): bool;

    public function setShowEarnableOnProduct(bool $showEarnableOnProduct): void;

    public function showEarnableInCart(): bool;

    public function setShowEarnableInCart(bool $showEarnableInCart): void;
}
