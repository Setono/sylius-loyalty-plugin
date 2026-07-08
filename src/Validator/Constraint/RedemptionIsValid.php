<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates, at checkout completion, that the redemption recorded on an order can still be honoured:
 * the account is enabled and its balance still covers the applied points. This guards the window
 * between the last order recalculation and completion, in which the balance could have dropped.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class RedemptionIsValid extends Constraint
{
    public string $insufficientBalanceMessage = 'setono_sylius_loyalty.order.redemption.insufficient_balance';

    public string $accountDisabledMessage = 'setono_sylius_loyalty.order.redemption.account_disabled';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
