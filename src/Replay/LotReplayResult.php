<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Replay;

/**
 * The outcome of replaying an account's ledger: the credit lots with their derived remaining, plus
 * any deficit carried when debits exceeded the open credit (the `allow_negative` clawback policy).
 */
final class LotReplayResult
{
    /**
     * @param list<Lot> $lots in the order the credits opened
     */
    public function __construct(
        private readonly array $lots,
        private readonly int $deficit,
    ) {
    }

    /**
     * @return list<Lot>
     */
    public function getLots(): array
    {
        return $this->lots;
    }

    /**
     * @return list<Lot> the lots that still have points
     */
    public function getOpenLots(): array
    {
        return array_values(array_filter($this->lots, static fn (Lot $lot): bool => $lot->getRemaining() > 0));
    }

    /**
     * The points still owed because debits exceeded the open credit (0 unless the balance is negative).
     */
    public function getDeficit(): int
    {
        return $this->deficit;
    }

    /**
     * The replay-derived balance: the sum of open-lot remainders minus any deficit. Must equal the
     * account's cached balance.
     */
    public function getBalance(): int
    {
        $balance = 0;
        foreach ($this->lots as $lot) {
            $balance += $lot->getRemaining();
        }

        return $balance - $this->deficit;
    }

    /**
     * The open points that expire at or before the given moment (never-expiring lots are excluded).
     */
    public function getPointsExpiringAtOrBefore(\DateTimeInterface $moment): int
    {
        $points = 0;
        foreach ($this->getOpenLots() as $lot) {
            $expiresAt = $lot->getExpiresAt();
            if (null !== $expiresAt && $expiresAt <= $moment) {
                $points += $lot->getRemaining();
            }
        }

        return $points;
    }
}
