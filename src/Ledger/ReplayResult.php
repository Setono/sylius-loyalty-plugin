<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Ledger;

use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransactionInterface;

final class ReplayResult
{
    /**
     * @param list<LotState> $lots in the order the lots were opened
     * @param list<ExpirationState> $expirations
     * @param int $balance the signed sum of all replayed transactions
     * @param int $deficit unserved debit points carried at the end of the replay (only under
     *                     the allow_negative clawback policy or when debits skipped expired lots)
     * @param list<string> $anomalies human-readable descriptions of ledger inconsistencies
     *                                encountered during replay (never auto-fixed)
     */
    public function __construct(
        public readonly array $lots,
        public readonly array $expirations,
        public readonly int $balance,
        public readonly int $deficit,
        public readonly array $anomalies,
    ) {
    }

    public function getLotState(CreditLoyaltyTransactionInterface $lot): ?LotState
    {
        foreach ($this->lots as $lotState) {
            if ($lotState->lot === $lot) {
                return $lotState;
            }
        }

        return null;
    }

    /**
     * @return list<LotState>
     */
    public function getOpenLots(): array
    {
        return array_values(array_filter($this->lots, static fn (LotState $lotState): bool => $lotState->isOpen()));
    }
}
