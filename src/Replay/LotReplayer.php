<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Replay;

use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\ExpireLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;

/**
 * Deterministically re-derives per-lot remaining points by replaying an account's ledger (§3.3):
 *
 * - a credit opens a lot with remaining = points (first covering any outstanding deficit);
 * - an expire transaction zeroes exactly its referenced lot;
 * - every other debit consumes open, non-expired lots in consumption order
 *   (expiresAt ASC NULLS LAST, occurredAt ASC, id ASC);
 * - a debit that exceeds the open credit carries the deficit, which the next credits absorb.
 *
 * The replayer never mutates the ledger and never produces a negative lot remainder.
 */
final class LotReplayer
{
    /**
     * @param iterable<LoyaltyTransactionInterface> $transactions the account's ledger
     */
    public function replay(iterable $transactions): LotReplayResult
    {
        /** @var list<Lot> $lots */
        $lots = [];

        /** @var \SplObjectStorage<CreditLoyaltyTransactionInterface, Lot> $lotByCredit */
        $lotByCredit = new \SplObjectStorage();

        $deficit = 0;

        foreach ($this->inReplayOrder($transactions) as $transaction) {
            if ($transaction instanceof CreditLoyaltyTransactionInterface) {
                $points = $transaction->getPoints();

                if ($deficit > 0) {
                    $applied = min($points, $deficit);
                    $deficit -= $applied;
                    $points -= $applied;
                }

                $lot = new Lot($transaction, $transaction->getPoints(), $points);
                $lots[] = $lot;
                $lotByCredit[$transaction] = $lot;

                continue;
            }

            if ($transaction instanceof ExpireLoyaltyTransactionInterface) {
                $lot = $transaction->getLot();
                if (null !== $lot && $lotByCredit->contains($lot)) {
                    $lotByCredit[$lot]->zero();
                }

                continue;
            }

            // any other debit consumes the open lots FIFO
            $amount = -$transaction->getPoints();
            foreach ($this->inConsumptionOrder($lots) as $lot) {
                if ($amount <= 0) {
                    break;
                }

                $amount -= $lot->consume($amount);
            }

            if ($amount > 0) {
                $deficit += $amount;
            }
        }

        return new LotReplayResult($lots, $deficit);
    }

    /**
     * @param iterable<LoyaltyTransactionInterface> $transactions
     *
     * @return list<LoyaltyTransactionInterface>
     */
    private function inReplayOrder(iterable $transactions): array
    {
        $ordered = $transactions instanceof \Traversable
            ? iterator_to_array($transactions, false)
            : array_values($transactions);

        usort(
            $ordered,
            static fn (LoyaltyTransactionInterface $a, LoyaltyTransactionInterface $b): int => [$a->getOccurredAt(), $a->getId()] <=> [$b->getOccurredAt(), $b->getId()],
        );

        return $ordered;
    }

    /**
     * @param list<Lot> $lots
     *
     * @return list<Lot> the open lots in consumption order
     */
    private function inConsumptionOrder(array $lots): array
    {
        $open = array_values(array_filter($lots, static fn (Lot $lot): bool => $lot->getRemaining() > 0));

        usort($open, static function (Lot $a, Lot $b): int {
            $expiresA = $a->getExpiresAt();
            $expiresB = $b->getExpiresAt();

            if (null !== $expiresA && null !== $expiresB) {
                $comparison = $expiresA <=> $expiresB;
                if (0 !== $comparison) {
                    return $comparison;
                }
            } elseif (null === $expiresA && null !== $expiresB) {
                return 1;
            } elseif (null !== $expiresA) {
                // reaching here means $expiresB is null: an expiring lot precedes a never-expiring one
                return -1;
            }

            $creditA = $a->getCredit();
            $creditB = $b->getCredit();

            return [$creditA->getOccurredAt(), $creditA->getId()] <=> [$creditB->getOccurredAt(), $creditB->getId()];
        });

        return $open;
    }
}
