<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

/**
 * A credit opens a "lot" of points. There is no stored remaining amount — per-lot remainders
 * are derived by replaying the account's ledger.
 */
interface CreditLoyaltyTransactionInterface extends LoyaltyTransactionInterface
{
    public function getExpiresAt(): ?\DateTimeImmutable;

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): void;
}
