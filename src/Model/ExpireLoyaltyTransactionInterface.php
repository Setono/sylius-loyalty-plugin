<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

/**
 * Closes exactly one expired lot, recording the replay-derived remaining points of that lot at
 * the moment of expiry — including a zero-point entry when the lot was fully consumed, which
 * closes the lot so the daily expiry selection stays exact.
 */
interface ExpireLoyaltyTransactionInterface extends DebitLoyaltyTransactionInterface
{
    public function getLot(): ?CreditLoyaltyTransactionInterface;

    public function setLot(?CreditLoyaltyTransactionInterface $lot): void;
}
