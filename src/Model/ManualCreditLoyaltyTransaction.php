<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

class ManualCreditLoyaltyTransaction extends CreditLoyaltyTransaction implements ManualCreditLoyaltyTransactionInterface
{
    use ManualLoyaltyTransactionTrait;

    public static function getType(): string
    {
        return 'manual_credit';
    }
}
