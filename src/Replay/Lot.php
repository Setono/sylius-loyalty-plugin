<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Replay;

use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransactionInterface;

/**
 * A credit lot as seen during ledger replay: the credit transaction that opened it, the points it
 * opened with, and how many of those points remain after FIFO consumption by later debits.
 */
final class Lot
{
    public function __construct(
        private readonly CreditLoyaltyTransactionInterface $credit,
        private readonly int $originalPoints,
        private int $remaining,
    ) {
    }

    public function getCredit(): CreditLoyaltyTransactionInterface
    {
        return $this->credit;
    }

    public function getOriginalPoints(): int
    {
        return $this->originalPoints;
    }

    public function getRemaining(): int
    {
        return $this->remaining;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->credit->getExpiresAt();
    }

    /**
     * Consumes up to $amount points from this lot and returns the amount actually consumed.
     *
     * @internal used by the replayer
     */
    public function consume(int $amount): int
    {
        $consumed = min($this->remaining, $amount);
        $this->remaining -= $consumed;

        return $consumed;
    }

    /**
     * @internal used by the replayer to close a lot that expired
     */
    public function zero(): void
    {
        $this->remaining = 0;
    }
}
