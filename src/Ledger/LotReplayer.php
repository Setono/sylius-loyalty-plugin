<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Ledger;

use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\DebitLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\ExpireLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;

final class LotReplayer implements LotReplayerInterface
{
    public function replay(iterable $transactions): ReplayResult
    {
        $transactions = $this->sortIntoReplayOrder($transactions);

        /** @var list<LotState> $lots */
        $lots = [];

        /** @var \SplObjectStorage<CreditLoyaltyTransactionInterface, LotState> $lotIndex */
        $lotIndex = new \SplObjectStorage();

        /** @var list<ExpirationState> $expirations */
        $expirations = [];

        /** @var list<string> $anomalies */
        $anomalies = [];

        /** @var list<array{debit: DebitLoyaltyTransactionInterface, points: int}> $deficits */
        $deficits = [];

        $balance = 0;

        foreach ($transactions as $transaction) {
            $balance += $transaction->getPoints();

            if ($transaction instanceof ExpireLoyaltyTransactionInterface) {
                $lot = $transaction->getLot();
                if (null === $lot || !isset($lotIndex[$lot])) {
                    $anomalies[] = sprintf(
                        'Expiration entry (id: %s) references a lot that does not precede it in the ledger',
                        self::describeId($transaction),
                    );

                    continue;
                }

                $lotState = $lotIndex[$lot];
                $expirations[] = new ExpirationState($transaction, $lotState->getRemaining());
                $lotState->closeByExpiration();

                continue;
            }

            if ($transaction instanceof CreditLoyaltyTransactionInterface) {
                if ($transaction->getPoints() < 0) {
                    $anomalies[] = sprintf('Credit (id: %s) has negative points', self::describeId($transaction));
                }

                $lotState = new LotState($transaction, max(0, $transaction->getPoints()));
                $lots[] = $lotState;
                $lotIndex[$transaction] = $lotState;

                $deficits = $this->serveDeficits($lotState, $deficits);

                continue;
            }

            if ($transaction instanceof DebitLoyaltyTransactionInterface) {
                if ($transaction->getPoints() > 0) {
                    $anomalies[] = sprintf('Debit (id: %s) has positive points', self::describeId($transaction));
                }

                $shortfall = $this->consume($transaction, abs($transaction->getPoints()), $lots);
                if ($shortfall > 0) {
                    $deficits[] = ['debit' => $transaction, 'points' => $shortfall];
                }

                continue;
            }

            $anomalies[] = sprintf(
                'Transaction (id: %s) is neither a credit nor a debit and cannot be replayed',
                self::describeId($transaction),
            );
        }

        return new ReplayResult(
            $lots,
            $expirations,
            $balance,
            array_sum(array_column($deficits, 'points')),
            $anomalies,
        );
    }

    /**
     * Consumes points from open, non-expired lots in consumption order and returns the
     * unserved remainder.
     *
     * @param list<LotState> $lots
     */
    private function consume(DebitLoyaltyTransactionInterface $debit, int $points, array $lots): int
    {
        $candidates = array_filter(
            $lots,
            static function (LotState $lotState) use ($debit): bool {
                if (!$lotState->isOpen()) {
                    return false;
                }

                $expiresAt = $lotState->lot->getExpiresAt();

                return null === $expiresAt || $expiresAt > $debit->getOccurredAt();
            },
        );

        usort($candidates, self::compareConsumptionOrder(...));

        foreach ($candidates as $lotState) {
            if ($points <= 0) {
                break;
            }

            $consumed = min($lotState->getRemaining(), $points);
            $lotState->consume(new Consumption($debit, $consumed));
            $points -= $consumed;
        }

        return $points;
    }

    /**
     * Serves a carried deficit from a newly opened lot, oldest debit first.
     *
     * @param list<array{debit: DebitLoyaltyTransactionInterface, points: int}> $deficits
     *
     * @return list<array{debit: DebitLoyaltyTransactionInterface, points: int}>
     */
    private function serveDeficits(LotState $lotState, array $deficits): array
    {
        foreach ($deficits as $key => $deficit) {
            if ($lotState->getRemaining() <= 0) {
                break;
            }

            $consumed = min($lotState->getRemaining(), $deficit['points']);
            $lotState->consume(new Consumption($deficit['debit'], $consumed));
            $deficits[$key]['points'] -= $consumed;
        }

        return array_values(array_filter(
            $deficits,
            static fn (array $deficit): bool => $deficit['points'] > 0,
        ));
    }

    /**
     * @param iterable<LoyaltyTransactionInterface> $transactions
     *
     * @return list<LoyaltyTransactionInterface>
     */
    private function sortIntoReplayOrder(iterable $transactions): array
    {
        $transactions = is_array($transactions) ? array_values($transactions) : iterator_to_array($transactions, false);

        usort(
            $transactions,
            static fn (LoyaltyTransactionInterface $a, LoyaltyTransactionInterface $b): int => [$a->getOccurredAt(), self::idOf($a)] <=> [$b->getOccurredAt(), self::idOf($b)],
        );

        return $transactions;
    }

    private static function compareConsumptionOrder(LotState $a, LotState $b): int
    {
        $aExpiresAt = $a->lot->getExpiresAt();
        $bExpiresAt = $b->lot->getExpiresAt();

        // expiresAt ASC NULLS LAST
        if (null === $aExpiresAt xor null === $bExpiresAt) {
            return null === $aExpiresAt ? 1 : -1;
        }

        return [$aExpiresAt, $a->lot->getOccurredAt(), self::idOf($a->lot)]
            <=> [$bExpiresAt, $b->lot->getOccurredAt(), self::idOf($b->lot)];
    }

    private static function idOf(LoyaltyTransactionInterface $transaction): int
    {
        $id = $transaction->getId();

        return is_int($id) ? $id : \PHP_INT_MAX;
    }

    private static function describeId(LoyaltyTransactionInterface $transaction): string
    {
        $id = $transaction->getId();

        return null === $id ? 'unsaved' : (string) $id;
    }
}
