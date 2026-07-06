<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tier\QualificationBasis;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;

/**
 * A way an account can qualify for a tier. Implementations are tagged
 * setono_sylius_loyalty.tier_qualification_basis (autoconfigured) and appear in the tier
 * form's basis select automatically.
 */
interface TierQualificationBasisInterface
{
    public function getCode(): string;

    /**
     * Translation key or plain label for the tier form's basis select.
     */
    public function getLabel(): string;

    /**
     * Translation key or plain label for the threshold field's unit hint (points, currency,
     * orders).
     */
    public function getUnitLabel(): string;

    /**
     * The account's qualification metric within the window. A null window means lifetime; a
     * custom basis may interpret or ignore the window — that is the escape hatch for exotic
     * windows.
     */
    public function calculate(LoyaltyAccountInterface $account, ?DateRange $window): int;
}
