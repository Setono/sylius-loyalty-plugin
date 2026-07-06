<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Ledger;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Setono\SyliusLoyaltyPlugin\Event\AwardingPoints;
use Setono\SyliusLoyaltyPlugin\Event\ClawingBackPoints;
use Setono\SyliusLoyaltyPlugin\Event\ExpiringPoints;
use Setono\SyliusLoyaltyPlugin\Event\ManualAdjustment;
use Setono\SyliusLoyaltyPlugin\Event\PointsClawedBack;
use Setono\SyliusLoyaltyPlugin\Event\PointsEarned;
use Setono\SyliusLoyaltyPlugin\Event\PointsExpired;
use Setono\SyliusLoyaltyPlugin\Event\PointsRedeemed;
use Setono\SyliusLoyaltyPlugin\Event\RedeemingPoints;
use Setono\SyliusLoyaltyPlugin\Event\RedemptionRolledBack;
use Setono\SyliusLoyaltyPlugin\Exception\AccountNotFoundException;
use Setono\SyliusLoyaltyPlugin\Exception\ExceptionInterface;
use Setono\SyliusLoyaltyPlugin\Exception\InsufficientBalanceException;
use Setono\SyliusLoyaltyPlugin\Exception\LedgerConflictException;
use Setono\SyliusLoyaltyPlugin\Model\ClawbackLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\ClawbackLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\DebitLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\ExpireLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\ExpireLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Model\ManualCreditLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\ManualCreditLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\ManualDebitLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\ManualDebitLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\RedeemRollbackLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\RedeemRollbackLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Tier\TierEvaluatorInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Webmozart\Assert\Assert;

final class LoyaltyLedger implements LoyaltyLedgerInterface
{
    /**
     * @param class-string<LoyaltyAccountInterface> $accountClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagerRegistry $managerRegistry,
        private readonly LoyaltyAccountProviderInterface $accountProvider,
        private readonly LoyaltyTransactionRepositoryInterface $transactionRepository,
        private readonly LotReplayerInterface $lotReplayer,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly TierEvaluatorInterface $tierEvaluator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly string $accountClass,
    ) {
    }

    public function earnOrder(
        OrderInterface $order,
        int $points,
        array $rulesBreakdown = [],
        ?int $basisAmount = null,
        ?\DateTimeImmutable $expiresAt = null,
    ): ?EarnOrderLoyaltyTransactionInterface {
        Assert::greaterThan($points, 0);

        $account = $this->accountFromOrder($order);
        if (null === $account) {
            return null;
        }

        if (!$account->isEnabled()) {
            $this->logger->info(sprintf(
                '[Loyalty] Skipped earning for order %s: the loyalty account (id: %d) is disabled',
                (string) $order->getNumber(),
                (int) $account->getId(),
            ));

            return null;
        }

        /** @var EarnOrderLoyaltyTransactionInterface|null $transaction */
        $transaction = $this->transactional(
            $account,
            function (LoyaltyAccountInterface $account, array &$postCommitEvents) use ($order, $points, $rulesBreakdown, $basisAmount, $expiresAt): ?EarnOrderLoyaltyTransactionInterface {
                $awarding = new AwardingPoints($account, $points, $expiresAt, $order, null, $rulesBreakdown);
                $this->eventDispatcher->dispatch($awarding);
                if ($awarding->isCancelled() || $awarding->getPoints() <= 0) {
                    return null;
                }

                $transaction = new EarnOrderLoyaltyTransaction();
                $transaction->setOrder($order);
                $transaction->setRulesBreakdown($rulesBreakdown);
                $transaction->setBasisAmount($basisAmount);

                $this->credit($account, $transaction, $awarding->getPoints(), $awarding->getExpiresAt());

                $this->evaluateTier($account);

                $postCommitEvents[] = new PointsEarned($transaction);

                return $transaction;
            },
        );

        return $transaction;
    }

    public function earnAction(
        LoyaltyAccountInterface $account,
        int $points,
        string $sourceIdentifier,
        array $rulesBreakdown = [],
        ?\DateTimeImmutable $expiresAt = null,
    ): ?EarnActionLoyaltyTransactionInterface {
        Assert::greaterThan($points, 0);

        if (!$account->isEnabled()) {
            $this->logger->info(sprintf(
                '[Loyalty] Skipped earning for source "%s": the loyalty account (id: %d) is disabled',
                $sourceIdentifier,
                (int) $account->getId(),
            ));

            return null;
        }

        /** @var EarnActionLoyaltyTransactionInterface|null $transaction */
        $transaction = $this->transactional(
            $account,
            function (LoyaltyAccountInterface $account, array &$postCommitEvents) use ($points, $sourceIdentifier, $rulesBreakdown, $expiresAt): ?EarnActionLoyaltyTransactionInterface {
                $awarding = new AwardingPoints($account, $points, $expiresAt, null, $sourceIdentifier, $rulesBreakdown);
                $this->eventDispatcher->dispatch($awarding);
                if ($awarding->isCancelled() || $awarding->getPoints() <= 0) {
                    return null;
                }

                $transaction = new EarnActionLoyaltyTransaction();
                $transaction->setSourceIdentifier($sourceIdentifier);
                $transaction->setRulesBreakdown($rulesBreakdown);

                $this->credit($account, $transaction, $awarding->getPoints(), $awarding->getExpiresAt());

                $this->evaluateTier($account);

                $postCommitEvents[] = new PointsEarned($transaction);

                return $transaction;
            },
        );

        return $transaction;
    }

    public function redeem(OrderInterface $order, int $points): ?RedeemLoyaltyTransactionInterface
    {
        Assert::greaterThan($points, 0);

        $account = $this->accountFromOrder($order);
        if (null === $account) {
            throw new AccountNotFoundException(sprintf(
                'No loyalty account can be resolved for order %s',
                (string) $order->getNumber(),
            ));
        }

        /** @var RedeemLoyaltyTransactionInterface|null $transaction */
        $transaction = $this->transactional(
            $account,
            function (LoyaltyAccountInterface $account, array &$postCommitEvents) use ($order, $points): ?RedeemLoyaltyTransactionInterface {
                if (null !== $this->transactionRepository->findRedeemTransaction($order)) {
                    $this->logger->info(sprintf(
                        '[Loyalty] Order %s already has a redemption; skipping duplicate debit',
                        (string) $order->getNumber(),
                    ));

                    return null;
                }

                if (!$account->isEnabled()) {
                    throw new LedgerConflictException(sprintf(
                        'The loyalty account (id: %d) is disabled and cannot redeem points',
                        (int) $account->getId(),
                    ));
                }

                if ($account->getBalance() < $points) {
                    throw InsufficientBalanceException::create($account, $points);
                }

                $redeeming = new RedeemingPoints($account, $points, $order);
                $this->eventDispatcher->dispatch($redeeming);
                if ($redeeming->isCancelled()) {
                    throw new LedgerConflictException('The redemption was cancelled by a listener');
                }

                $transaction = new RedeemLoyaltyTransaction();
                $transaction->setOrder($order);

                $this->debit($account, $transaction, $points);

                $postCommitEvents[] = new PointsRedeemed($transaction);

                return $transaction;
            },
        );

        return $transaction;
    }

    public function rollbackRedeem(OrderInterface $order): ?RedeemRollbackLoyaltyTransactionInterface
    {
        $redeem = $this->transactionRepository->findRedeemTransaction($order);
        if (null === $redeem) {
            return null;
        }

        $account = $redeem->getAccount();
        Assert::notNull($account);

        /** @var RedeemRollbackLoyaltyTransactionInterface|null $transaction */
        $transaction = $this->transactional(
            $account,
            function (LoyaltyAccountInterface $account, array &$postCommitEvents) use ($redeem): ?RedeemRollbackLoyaltyTransactionInterface {
                if ($this->transactionRepository->hasRollback($redeem)) {
                    $this->logger->info(sprintf(
                        '[Loyalty] The redemption (id: %d) was already rolled back; skipping',
                        (int) $redeem->getId(),
                    ));

                    return null;
                }

                $transaction = new RedeemRollbackLoyaltyTransaction();
                $transaction->setRedeem($redeem);

                $this->credit(
                    $account,
                    $transaction,
                    abs($redeem->getPoints()),
                    $this->resolveRollbackExpiry($account, $redeem),
                    countsTowardLifetimeEarned: false,
                );

                $postCommitEvents[] = new RedemptionRolledBack($transaction);

                return $transaction;
            },
        );

        return $transaction;
    }

    public function expire(LoyaltyAccountInterface $account, ?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();

        /** @var list<ExpireLoyaltyTransactionInterface> $transactions */
        $transactions = $this->transactional(
            $account,
            function (LoyaltyAccountInterface $account, array &$postCommitEvents) use ($now): array {
                $replay = $this->lotReplayer->replay($this->transactionRepository->findForReplay($account));

                $transactions = [];
                foreach ($replay->lots as $lotState) {
                    $expiresAt = $lotState->lot->getExpiresAt();
                    if (null === $expiresAt || $expiresAt > $now || $lotState->isClosedByExpiration()) {
                        continue;
                    }

                    $expiring = new ExpiringPoints($account, $lotState->lot, $lotState->getRemaining());
                    $this->eventDispatcher->dispatch($expiring);
                    if ($expiring->isCancelled()) {
                        continue;
                    }

                    $transaction = new ExpireLoyaltyTransaction();
                    $transaction->setLot($lotState->lot);

                    // Zero-point entries close fully consumed lots so the daily selection stays exact
                    $this->debit($account, $transaction, $lotState->getRemaining(), occurredAt: $now);

                    $postCommitEvents[] = new PointsExpired($transaction);
                    $transactions[] = $transaction;
                }

                return $transactions;
            },
        ) ?? [];

        return $transactions;
    }

    public function clawback(OrderInterface $order, int $points): ?ClawbackLoyaltyTransactionInterface
    {
        Assert::greaterThan($points, 0);

        $earn = $this->transactionRepository->findEarnOrderTransaction($order);
        if (null === $earn) {
            return null;
        }

        $account = $earn->getAccount();
        Assert::notNull($account);

        /** @var ClawbackLoyaltyTransactionInterface|null $transaction */
        $transaction = $this->transactional(
            $account,
            function (LoyaltyAccountInterface $account, array &$postCommitEvents) use ($order, $points, $earn): ?ClawbackLoyaltyTransactionInterface {
                $clawingBack = new ClawingBackPoints($account, $points, $order, $earn);
                $this->eventDispatcher->dispatch($clawingBack);
                if ($clawingBack->isCancelled()) {
                    return null;
                }

                $points = $clawingBack->getPoints();

                $channel = $account->getChannel();
                Assert::notNull($channel);
                $program = $this->programProvider->getByChannel($channel);
                if (LoyaltyProgramInterface::CLAWBACK_POLICY_CLAMP_TO_ZERO === $program->getClawbackPolicy()) {
                    // Reduce the debit at write time so the balance lands at exactly zero; the
                    // ledger entry records what was actually debited
                    $points = min($points, max(0, $account->getBalance()));
                }

                $transaction = new ClawbackLoyaltyTransaction();
                $transaction->setOrder($order);
                $transaction->setEarn($earn);

                $this->debit($account, $transaction, $points);

                $postCommitEvents[] = new PointsClawedBack($transaction);

                return $transaction;
            },
        );

        return $transaction;
    }

    public function manualCredit(
        LoyaltyAccountInterface $account,
        int $points,
        string $reason,
        string $note,
        ?AdminUserInterface $adminUser = null,
    ): ManualCreditLoyaltyTransactionInterface {
        Assert::greaterThan($points, 0);
        Assert::stringNotEmpty($note);

        /** @var ManualCreditLoyaltyTransactionInterface|null $transaction */
        $transaction = $this->transactional(
            $account,
            function (LoyaltyAccountInterface $account, array &$postCommitEvents) use ($points, $reason, $note, $adminUser): ManualCreditLoyaltyTransactionInterface {
                $transaction = new ManualCreditLoyaltyTransaction();
                $transaction->setReason($reason);
                $transaction->setNote($note);
                $transaction->setAdminUser($adminUser);

                $this->credit($account, $transaction, $points, null);

                $this->evaluateTier($account);

                $postCommitEvents[] = new ManualAdjustment($transaction);

                return $transaction;
            },
        );

        Assert::notNull($transaction);

        return $transaction;
    }

    public function manualDebit(
        LoyaltyAccountInterface $account,
        int $points,
        string $reason,
        string $note,
        ?AdminUserInterface $adminUser = null,
    ): ManualDebitLoyaltyTransactionInterface {
        Assert::greaterThan($points, 0);
        Assert::stringNotEmpty($note);

        /** @var ManualDebitLoyaltyTransactionInterface|null $transaction */
        $transaction = $this->transactional(
            $account,
            function (LoyaltyAccountInterface $account, array &$postCommitEvents) use ($points, $reason, $note, $adminUser): ManualDebitLoyaltyTransactionInterface {
                $transaction = new ManualDebitLoyaltyTransaction();
                $transaction->setReason($reason);
                $transaction->setNote($note);
                $transaction->setAdminUser($adminUser);

                $this->debit($account, $transaction, $points);

                $postCommitEvents[] = new ManualAdjustment($transaction);

                return $transaction;
            },
        );

        Assert::notNull($transaction);

        return $transaction;
    }

    /**
     * Runs the given callback with the account row locked (pessimistic write) inside a
     * transaction, then dispatches the collected post-commit events. A unique constraint
     * violation is an idempotent no-op by design; any plugin exception leaves the (closed)
     * entity manager reset so the surrounding request stays usable.
     *
     * @param callable(LoyaltyAccountInterface, array<int, object>): mixed $callback
     */
    private function transactional(LoyaltyAccountInterface $account, callable $callback): mixed
    {
        $accountId = $account->getId();
        Assert::notNull($accountId, 'The loyalty account must be persisted before writing ledger entries');

        /** @var array<int, object> $postCommitEvents */
        $postCommitEvents = [];

        try {
            $result = $this->entityManager->wrapInTransaction(
                function () use ($accountId, $callback, &$postCommitEvents): mixed {
                    $account = $this->entityManager
                        ->getRepository($this->accountClass)
                        ->find($accountId, LockMode::PESSIMISTIC_WRITE)
                    ;
                    if (!$account instanceof LoyaltyAccountInterface) {
                        throw new AccountNotFoundException(sprintf('The loyalty account (id: %s) no longer exists', (string) $accountId));
                    }

                    return $callback($account, $postCommitEvents);
                },
            );
        } catch (UniqueConstraintViolationException $e) {
            // The write already happened (event redelivery, concurrent request) — a no-op by design
            $this->managerRegistry->resetManager();
            $this->logger->info(sprintf('[Loyalty] Idempotent no-op for account (id: %d): %s', $accountId, $e->getMessage()));

            return null;
        } catch (ExceptionInterface $e) {
            $this->managerRegistry->resetManager();

            throw $e;
        }

        foreach ($postCommitEvents as $event) {
            $this->eventDispatcher->dispatch($event);
        }

        return $result;
    }

    /**
     * The qualification bases derive their metrics from ledger queries, so the pending credit
     * must be flushed (still inside the surrounding transaction) before the tier evaluator
     * runs — otherwise the earn that crosses a threshold would not upgrade until the nightly
     * reconciliation.
     */
    private function evaluateTier(LoyaltyAccountInterface $account): void
    {
        $this->entityManager->flush();
        $this->tierEvaluator->evaluate($account);
    }

    private function credit(
        LoyaltyAccountInterface $account,
        CreditLoyaltyTransaction $transaction,
        int $points,
        ?\DateTimeImmutable $expiresAt,
        bool $countsTowardLifetimeEarned = true,
    ): void {
        $transaction->setAccount($account);
        $transaction->setPoints($points);
        $transaction->setExpiresAt($expiresAt);
        $this->entityManager->persist($transaction);

        $account->setBalance($account->getBalance() + $points);
        if ($countsTowardLifetimeEarned) {
            $account->setLifetimeEarned($account->getLifetimeEarned() + $points);
        }
    }

    private function debit(
        LoyaltyAccountInterface $account,
        DebitLoyaltyTransaction $transaction,
        int $points,
        ?\DateTimeImmutable $occurredAt = null,
    ): void {
        $transaction->setAccount($account);
        $transaction->setPoints(-$points);
        if (null !== $occurredAt) {
            $transaction->setOccurredAt($occurredAt);
        }
        $this->entityManager->persist($transaction);

        $account->setBalance($account->getBalance() - $points);
    }

    /**
     * The new lot restores the redeemed points carrying the earliest surviving expiry of the
     * lots the replay attributes to the rolled-back debit (simplification accepted by design).
     */
    private function resolveRollbackExpiry(LoyaltyAccountInterface $account, RedeemLoyaltyTransactionInterface $redeem): ?\DateTimeImmutable
    {
        $replay = $this->lotReplayer->replay($this->transactionRepository->findForReplay($account));
        $now = new \DateTimeImmutable();

        $earliest = null;
        foreach ($replay->lots as $lotState) {
            foreach ($lotState->getConsumptions() as $consumption) {
                if ($consumption->debit !== $redeem) {
                    continue;
                }

                $expiresAt = $lotState->lot->getExpiresAt();
                if (null === $expiresAt || $expiresAt <= $now) {
                    continue;
                }

                if (null === $earliest || $expiresAt < $earliest) {
                    $earliest = $expiresAt;
                }
            }
        }

        return $earliest;
    }

    private function accountFromOrder(OrderInterface $order): ?LoyaltyAccountInterface
    {
        $customer = $order->getCustomer();
        $channel = $order->getChannel();

        if (!$customer instanceof CustomerInterface || null === $channel) {
            return null;
        }

        return $this->accountProvider->getByCustomerAndChannel($customer, $channel);
    }
}
