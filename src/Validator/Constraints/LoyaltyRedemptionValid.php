<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Re-validates the applied redemption on checkout completion: the balance must still cover
 * the applied points (they may have been spent on another device) and the account must still
 * be enabled. Completion is blocked with an actionable error — totals are never silently
 * changed at the final step.
 */
final class LoyaltyRedemptionValid extends Constraint
{
    public string $insufficientBalanceMessage = 'setono_sylius_loyalty.checkout.insufficient_balance';

    public string $accountDisabledMessage = 'setono_sylius_loyalty.checkout.account_disabled';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return LoyaltyRedemptionValidValidator::class;
    }
}
