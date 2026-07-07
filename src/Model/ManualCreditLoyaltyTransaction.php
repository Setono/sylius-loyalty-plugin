<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

class ManualCreditLoyaltyTransaction extends CreditLoyaltyTransaction implements ManualCreditLoyaltyTransactionInterface
{
    use ManualLoyaltyTransactionTrait;

    public static function getDiscriminator(): string
    {
        return 'manual_credit';
    }
}
