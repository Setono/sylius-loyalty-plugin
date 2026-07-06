<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Channel\Model\ChannelAwareInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * The loyalty program a channel runs: its parameters (conversion rate, expiry policy, earning
 * basis, policies, referral rewards). One row per channel, created with defaults on first access.
 *
 * Earning rules and tiers do not belong to the program relationally; they reference the channel
 * directly. Note that the earning rate (currency -> points) is deliberately not a program
 * parameter: it is defined by earning rules.
 */
interface LoyaltyProgramInterface extends ResourceInterface, ChannelAwareInterface
{
    public const AWARD_ORDER_POINTS_AT_PAYMENT_PAID = 'payment_paid';

    public const AWARD_ORDER_POINTS_AT_ORDER_FULFILLED = 'order_fulfilled';

    public const EARNING_BASIS_ITEMS_TOTAL = 'items_total';

    public const EARNING_BASIS_ORDER_TOTAL = 'order_total';

    public const ROUNDING_FLOOR = 'floor';

    public const ROUNDING_ROUND = 'round';

    public const ROUNDING_CEIL = 'ceil';

    public const CLAWBACK_POLICY_ALLOW_NEGATIVE = 'allow_negative';

    public const CLAWBACK_POLICY_CLAMP_TO_ZERO = 'clamp_to_zero';

    public const TIER_EVALUATION_WINDOW_CALENDAR_YEAR = 'calendar_year';

    public const TIER_EVALUATION_WINDOW_ROLLING_12_MONTHS = 'rolling_12_months';

    public const TIER_EVALUATION_WINDOW_LIFETIME = 'lifetime';

    /**
     * The single order-lifecycle moment the order pipeline fires. Deliberately one per program,
     * never per rule: the one-earn-per-order idempotency constraint and the clawback lookup both
     * depend on a single earn entry per order.
     */
    public function getAwardOrderPointsAt(): string;

    public function setAwardOrderPointsAt(string $awardOrderPointsAt): void;

    public function getEarningBasis(): string;

    public function setEarningBasis(string $earningBasis): void;

    public function isIncludeTaxes(): bool;

    public function setIncludeTaxes(bool $includeTaxes): void;

    public function getRounding(): string;

    public function setRounding(string $rounding): void;

    /**
     * Points -> currency when spending: getRedemptionConversionPoints() points are worth
     * getRedemptionConversionAmount() minor units.
     */
    public function getRedemptionConversionPoints(): int;

    public function setRedemptionConversionPoints(int $redemptionConversionPoints): void;

    public function getRedemptionConversionAmount(): int;

    public function setRedemptionConversionAmount(int $redemptionConversionAmount): void;

    public function getMinRedeemPoints(): int;

    public function setMinRedeemPoints(int $minRedeemPoints): void;

    /**
     * Cap of the order items total coverable by points, in percent (0-100).
     */
    public function getMaxRedeemPercentOfOrder(): int;

    public function setMaxRedeemPercentOfOrder(int $maxRedeemPercentOfOrder): void;

    /**
     * Null means points never expire.
     */
    public function getPointsExpiryDays(): ?int;

    public function setPointsExpiryDays(?int $pointsExpiryDays): void;

    public function getClawbackPolicy(): string;

    public function setClawbackPolicy(string $clawbackPolicy): void;

    /**
     * Whether to award points for pre-registration guest orders when the guest registers.
     */
    public function isRetroactiveGuestPoints(): bool;

    public function setRetroactiveGuestPoints(bool $retroactiveGuestPoints): void;

    public function getTierEvaluationWindow(): string;

    public function setTierEvaluationWindow(string $tierEvaluationWindow): void;

    public function getTierDowngradeGraceDays(): int;

    public function setTierDowngradeGraceDays(int $tierDowngradeGraceDays): void;

    public function getReferralReferrerPoints(): int;

    public function setReferralReferrerPoints(int $referralReferrerPoints): void;

    public function getReferralRefereePoints(): int;

    public function setReferralRefereePoints(int $referralRefereePoints): void;

    /**
     * Minimum items total (in minor units of the channel base currency) of the referee's first
     * order for a referral to qualify.
     */
    public function getReferralMinOrderTotal(): int;

    public function setReferralMinOrderTotal(int $referralMinOrderTotal): void;

    public function getReferralPendingExpiryDays(): int;

    public function setReferralPendingExpiryDays(int $referralPendingExpiryDays): void;

    public function isShowEarnableOnProduct(): bool;

    public function setShowEarnableOnProduct(bool $showEarnableOnProduct): void;

    public function isShowEarnableInCart(): bool;

    public function setShowEarnableInCart(bool $showEarnableInCart): void;
}
