<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Ledger;

use Setono\SyliusLoyaltyPlugin\Exception\InsufficientBalanceException;
use Setono\SyliusLoyaltyPlugin\Exception\LedgerConflictException;
use Setono\SyliusLoyaltyPlugin\Model\ClawbackLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\ExpireLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\ManualCreditLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\ManualDebitLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\RedeemRollbackLoyaltyTransactionInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * The single write path into the loyalty ledger. Every method locks the account row
 * (pessimistic write lock inside a transaction), appends entries, and maintains the cached
 * balance. All point arguments are positive magnitudes; the ledger applies the sign.
 *
 * Methods returning null performed no write: the operation was an idempotent replay, the
 * account is disabled (earning), or a listener cancelled the write via the pre-event.
 */
interface LoyaltyLedgerInterface
{
    /**
     * @param array<string, mixed> $rulesBreakdown
     */
    public function earnOrder(
        OrderInterface $order,
        int $points,
        array $rulesBreakdown = [],
        ?int $basisAmount = null,
        ?\DateTimeImmutable $expiresAt = null,
    ): ?EarnOrderLoyaltyTransactionInterface;

    /**
     * @param array<string, mixed> $rulesBreakdown
     */
    public function earnAction(
        LoyaltyAccountInterface $account,
        int $points,
        string $sourceIdentifier,
        array $rulesBreakdown = [],
        ?\DateTimeImmutable $expiresAt = null,
    ): ?EarnActionLoyaltyTransactionInterface;

    /**
     * Debits the applied points when the order completes checkout. The balance and the
     * account's enabled state are re-validated inside the account lock.
     *
     * @throws InsufficientBalanceException if the balance no longer covers the points
     * @throws LedgerConflictException if the account is disabled or a listener cancelled the redemption
     */
    public function redeem(OrderInterface $order, int $points): ?RedeemLoyaltyTransactionInterface;

    /**
     * Restores the points of the order's redemption as a new lot carrying the earliest
     * surviving expiry of the lots the replay attributes to the rolled-back debit.
     */
    public function rollbackRedeem(OrderInterface $order): ?RedeemRollbackLoyaltyTransactionInterface;

    /**
     * Writes one expiration entry per expired open lot — including zero-point entries closing
     * fully consumed lots. Runs for disabled accounts too, so the liability doesn't freeze.
     *
     * @return list<ExpireLoyaltyTransactionInterface>
     */
    public function expire(LoyaltyAccountInterface $account, ?\DateTimeImmutable $now = null): array;

    /**
     * Debits the points earned for the order (looked up via its earn transaction; no-op if
     * none). Public extension point for project-level (partial) refund integrations.
     */
    public function clawback(OrderInterface $order, int $points): ?ClawbackLoyaltyTransactionInterface;

    public function manualCredit(
        LoyaltyAccountInterface $account,
        int $points,
        string $reason,
        string $note,
        ?AdminUserInterface $adminUser = null,
    ): ManualCreditLoyaltyTransactionInterface;

    public function manualDebit(
        LoyaltyAccountInterface $account,
        int $points,
        string $reason,
        string $note,
        ?AdminUserInterface $adminUser = null,
    ): ManualDebitLoyaltyTransactionInterface;
}
