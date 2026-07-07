<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

/**
 * A credit (positive points). A credit with an `expiresAt` is a "lot" that points are consumed from
 * FIFO during ledger replay.
 */
interface CreditLoyaltyTransactionInterface extends LoyaltyTransactionInterface
{
    public function getExpiresAt(): ?\DateTimeInterface;

    public function setExpiresAt(?\DateTimeInterface $expiresAt): void;
}
