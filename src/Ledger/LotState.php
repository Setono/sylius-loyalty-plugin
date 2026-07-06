<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Ledger;

use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransactionInterface;

/**
 * The replay-derived state of a single lot. There is no stored remaining amount anywhere —
 * this object is computed by the LotReplayer and discarded after use.
 */
final class LotState
{
    /** @var list<Consumption> */
    private array $consumptions = [];

    private bool $closedByExpiration = false;

    public function __construct(
        public readonly CreditLoyaltyTransactionInterface $lot,
        private int $remaining,
    ) {
    }

    public function getRemaining(): int
    {
        return $this->remaining;
    }

    /**
     * @return list<Consumption>
     */
    public function getConsumptions(): array
    {
        return $this->consumptions;
    }

    public function isClosedByExpiration(): bool
    {
        return $this->closedByExpiration;
    }

    public function isOpen(): bool
    {
        return $this->remaining > 0 && !$this->closedByExpiration;
    }

    /**
     * @internal only the LotReplayer mutates lot states
     */
    public function consume(Consumption $consumption): void
    {
        $this->remaining -= $consumption->points;
        $this->consumptions[] = $consumption;
    }

    /**
     * @internal only the LotReplayer mutates lot states
     */
    public function closeByExpiration(): void
    {
        $this->remaining = 0;
        $this->closedByExpiration = true;
    }
}
