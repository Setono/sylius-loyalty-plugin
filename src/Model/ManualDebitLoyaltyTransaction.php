<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

class ManualDebitLoyaltyTransaction extends DebitLoyaltyTransaction implements ManualDebitLoyaltyTransactionInterface
{
    use ManualLoyaltyTransactionTrait;

    public static function getType(): string
    {
        return 'manual_debit';
    }
}
