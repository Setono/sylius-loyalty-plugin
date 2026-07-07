<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Inspector;

use Setono\SyliusLoyaltyPlugin\Ledger\LotReplayerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;

/**
 * Replays an account's ledger and checks the ledger invariants:
 *  1. the signed sum of all entries equals the cached balance,
 *  2. replay never produces a negative lot remainder,
 *  3. every expiration entry's points equal the replay-derived remaining of its lot at that
 *     moment,
 *  4. (warning, not corruption) lots past their expiry with remaining > 0 — the expiry cron
 *     is behind or a listener deferred the expiration.
 */
final class AccountInspector implements AccountInspectorInterface
{
    public function __construct(
        private readonly LoyaltyTransactionRepositoryInterface $transactionRepository,
        private readonly LotReplayerInterface $lotReplayer,
    ) {
    }

    public function inspect(LoyaltyAccountInterface $account, ?\DateTimeImmutable $now = null): AccountInspection
    {
        $now ??= new \DateTimeImmutable();
        $replay = $this->lotReplayer->replay($this->transactionRepository->findForReplay($account));

        $errors = $replay->anomalies;

        if ($replay->balance !== $account->getBalance()) {
            $errors[] = sprintf(
                'The cached balance (%d) differs from the ledger sum (%d)',
                $account->getBalance(),
                $replay->balance,
            );
        }

        foreach ($replay->expirations as $expiration) {
            if (abs($expiration->expiration->getPoints()) !== $expiration->remainingBefore) {
                $errors[] = sprintf(
                    'Expiration entry (id: %s) debits %d points but its lot had %d remaining at that moment',
                    (string) $expiration->expiration->getId(),
                    abs($expiration->expiration->getPoints()),
                    $expiration->remainingBefore,
                );
            }
        }

        $warnings = [];
        foreach ($replay->lots as $lotState) {
            $expiresAt = $lotState->lot->getExpiresAt();
            if ($lotState->isOpen() && null !== $expiresAt && $expiresAt < $now) {
                $warnings[] = sprintf(
                    'Lot (id: %s) expired %s with %d points still open — the expiry cron is behind or a listener deferred it',
                    (string) $lotState->lot->getId(),
                    $expiresAt->format('Y-m-d'),
                    $lotState->getRemaining(),
                );
            }
        }

        return new AccountInspection($account, $replay, $errors, $warnings);
    }
}
