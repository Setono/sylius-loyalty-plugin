<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Ledger;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusLoyaltyPlugin\Event\AwardingPoints;
use Setono\SyliusLoyaltyPlugin\Event\ClawingBackPoints;
use Setono\SyliusLoyaltyPlugin\Event\PointsClawedBack;
use Setono\SyliusLoyaltyPlugin\Event\PointsEarned;
use Setono\SyliusLoyaltyPlugin\Exception\AccountNotFoundException;
use Setono\SyliusLoyaltyPlugin\Exception\RuntimeException;
use Setono\SyliusLoyaltyPlugin\Model\ClawbackLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\ExpireLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\ExpireLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\RedeemRollbackLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Replay\LotReplayer;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class LoyaltyLedger implements LoyaltyLedgerInterface, LoggerAwareInterface
{
    use ORMTrait;
    use LoggerAwareTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LotReplayer $replayer,
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->logger = new NullLogger();
    }

    public function earnForOrder(
        LoyaltyAccountInterface $account,
        OrderInterface $order,
        int $points,
        int $basisAmount = 0,
        array $rulesBreakdown = [],
        ?\DateTimeInterface $expiresAt = null,
    ): void {
        $this->award(
            $account,
            $points,
            $expiresAt,
            static fn (EntityManagerInterface $manager, LoyaltyAccountInterface $account): bool => null !== $manager
                ->getRepository(EarnOrderLoyaltyTransaction::class)
                ->findOneBy(['account' => $account, 'order' => $order]),
            static function () use ($order, $basisAmount, $rulesBreakdown): EarnOrderLoyaltyTransaction {
                $transaction = new EarnOrderLoyaltyTransaction();
                $transaction->setOrder($order);
                $transaction->setBasisAmount($basisAmount);
                $transaction->setRulesBreakdown($rulesBreakdown);

                return $transaction;
            },
        );
    }

    public function earnForAction(
        LoyaltyAccountInterface $account,
        string $sourceIdentifier,
        int $points,
        array $rulesBreakdown = [],
        ?\DateTimeInterface $expiresAt = null,
    ): void {
        $this->award(
            $account,
            $points,
            $expiresAt,
            static fn (EntityManagerInterface $manager, LoyaltyAccountInterface $account): bool => null !== $manager
                ->getRepository(EarnActionLoyaltyTransaction::class)
                ->findOneBy(['account' => $account, 'sourceIdentifier' => $sourceIdentifier]),
            static function () use ($sourceIdentifier, $rulesBreakdown): EarnActionLoyaltyTransaction {
                $transaction = new EarnActionLoyaltyTransaction();
                $transaction->setSourceIdentifier($sourceIdentifier);
                $transaction->setRulesBreakdown($rulesBreakdown);

                return $transaction;
            },
        );
    }

    public function clawback(
        OrderInterface $order,
        string $clawbackPolicy = LoyaltyProgramInterface::CLAWBACK_POLICY_CLAMP_TO_ZERO,
    ): void {
        $manager = $this->getManager(EarnOrderLoyaltyTransaction::class);

        $earns = $manager->getRepository(EarnOrderLoyaltyTransaction::class)->findBy(['order' => $order]);
        foreach ($earns as $earn) {
            $account = $earn->getAccount();
            if ($account instanceof LoyaltyAccountInterface) {
                $this->clawbackEarn($account, $earn, $order, $clawbackPolicy);
            }
        }
    }

    private function clawbackEarn(
        LoyaltyAccountInterface $account,
        EarnOrderLoyaltyTransaction $earn,
        OrderInterface $order,
        string $clawbackPolicy,
    ): void {
        $manager = $this->getManager($account);

        $manager->wrapInTransaction(function (EntityManagerInterface $manager) use ($account, $earn, $order, $clawbackPolicy): void {
            $account = $this->lock($manager, $account);

            // One clawback per earn; the account lock makes this check race-free.
            if (null !== $manager->getRepository(ClawbackLoyaltyTransaction::class)->findOneBy(['earn' => $earn])) {
                $this->logger?->info('Ignored a duplicate loyalty clawback (idempotent no-op).');

                return;
            }

            $event = new ClawingBackPoints($account, $earn, $this->clampClawback($earn->getPoints(), $earn, $account, $clawbackPolicy));
            $this->eventDispatcher->dispatch($event);
            if ($event->cancelled) {
                return;
            }

            // A listener may lower the debit (or cancel it), but the clamp is re-applied afterwards so it
            // can never reverse more than was earned or, under clamp-to-zero, drive the balance negative —
            // and a negative adjustment can never flip the debit into a credit.
            $debit = $this->clampClawback($event->points, $earn, $account, $clawbackPolicy);

            $transaction = new ClawbackLoyaltyTransaction();
            $transaction->setAccount($account);
            $transaction->setOrder($order);
            $transaction->setEarn($earn);
            $transaction->setPoints(-$debit);
            $transaction->setOccurredAt(new \DateTimeImmutable());

            $manager->persist($transaction);
            $manager->flush();

            $this->recomputeCaches($manager, $account);
            $manager->flush();

            $this->eventDispatcher->dispatch(new PointsClawedBack($transaction));
        });
    }

    public function expire(LoyaltyAccountInterface $account, \DateTimeInterface $asOf): void
    {
        $manager = $this->getManager($account);

        $manager->wrapInTransaction(function (EntityManagerInterface $manager) use ($account, $asOf): void {
            $account = $this->lock($manager, $account);

            $transactions = $manager->getRepository(LoyaltyTransaction::class)->findBy(['account' => $account]);

            // Lots that already have an expire row must not be expired again. The replay also zeroes them,
            // so this guard is what keeps a fully-consumed expired lot from getting a duplicate close row.
            $expiredLotIds = [];
            foreach ($transactions as $transaction) {
                if ($transaction instanceof ExpireLoyaltyTransactionInterface) {
                    $lot = $transaction->getLot();
                    if (null !== $lot) {
                        $expiredLotIds[(int) $lot->getId()] = true;
                    }
                }
            }

            $wrote = false;
            foreach ($this->replayer->replay($transactions)->getLots() as $lot) {
                $credit = $lot->getCredit();
                $expiresAt = $credit->getExpiresAt();
                $creditId = $credit->getId();
                if (null === $expiresAt || $expiresAt > $asOf || (null !== $creditId && isset($expiredLotIds[$creditId]))) {
                    continue;
                }

                // One expire row per lot, debiting whatever it had left — a zero-point close row when the
                // lot was already fully consumed, so the lot is never revisited on the next run.
                $expiration = new ExpireLoyaltyTransaction();
                $expiration->setAccount($account);
                $expiration->setLot($credit);
                $expiration->setPoints(-$lot->getRemaining());
                $expiration->setOccurredAt(new \DateTimeImmutable());
                $manager->persist($expiration);
                $wrote = true;
            }

            if ($wrote) {
                $manager->flush();
                $this->recomputeCaches($manager, $account);
                $manager->flush();
            }
        });
    }

    /**
     * The debit a clawback may write: never negative, never more than the lot earned, and — under
     * clamp-to-zero — never more than the current balance.
     */
    private function clampClawback(int $points, EarnOrderLoyaltyTransaction $earn, LoyaltyAccountInterface $account, string $clawbackPolicy): int
    {
        $points = max(0, min($points, $earn->getPoints()));

        if (LoyaltyProgramInterface::CLAWBACK_POLICY_CLAMP_TO_ZERO === $clawbackPolicy) {
            $points = min($points, $account->getBalance());
        }

        return $points;
    }

    /**
     * @param callable(EntityManagerInterface, LoyaltyAccountInterface): bool $alreadyAwarded
     * @param callable(): CreditLoyaltyTransactionInterface $createTransaction
     */
    private function award(
        LoyaltyAccountInterface $account,
        int $points,
        ?\DateTimeInterface $expiresAt,
        callable $alreadyAwarded,
        callable $createTransaction,
    ): void {
        $manager = $this->getManager($account);

        $manager->wrapInTransaction(function (EntityManagerInterface $manager) use ($account, $points, $expiresAt, $alreadyAwarded, $createTransaction): void {
            $account = $this->lock($manager, $account);

            // The account lock serialises writes for this account, so this check is race-free; the
            // unique constraints (§3.3) are the database-level backstop.
            if ($alreadyAwarded($manager, $account)) {
                $this->logger?->info('Ignored a duplicate loyalty award (idempotent no-op).');

                return;
            }

            $event = new AwardingPoints($account, $points, $expiresAt);
            $this->eventDispatcher->dispatch($event);
            if ($event->cancelled) {
                return;
            }

            $transaction = $createTransaction();
            $transaction->setAccount($account);
            $transaction->setPoints($event->points);
            $transaction->setExpiresAt($event->expiresAt);
            $transaction->setOccurredAt(new \DateTimeImmutable());

            $manager->persist($transaction);
            $manager->flush();

            $this->recomputeCaches($manager, $account);
            $manager->flush();

            $this->eventDispatcher->dispatch(new PointsEarned($transaction));
        });
    }

    private function lock(EntityManagerInterface $manager, LoyaltyAccountInterface $account): LoyaltyAccountInterface
    {
        $id = $account->getId();
        if (null === $id) {
            throw new RuntimeException('Cannot write to the ledger for an unpersisted loyalty account.');
        }

        $locked = $manager->find(LoyaltyAccountInterface::class, $id);
        if (!$locked instanceof LoyaltyAccountInterface) {
            throw new AccountNotFoundException(sprintf('Loyalty account %d not found.', $id));
        }

        $manager->lock($locked, LockMode::PESSIMISTIC_WRITE);

        return $locked;
    }

    private function recomputeCaches(EntityManagerInterface $manager, LoyaltyAccountInterface $account): void
    {
        $balance = (int) $manager
            ->createQuery(sprintf('SELECT COALESCE(SUM(t.points), 0) FROM %s t WHERE t.account = :account', LoyaltyTransaction::class))
            ->setParameter('account', $account)
            ->getSingleScalarResult();

        $lifetimeEarned = (int) $manager
            ->createQuery(sprintf(
                'SELECT COALESCE(SUM(t.points), 0) FROM %s t WHERE t.account = :account AND t.points > 0 AND t NOT INSTANCE OF %s',
                LoyaltyTransaction::class,
                RedeemRollbackLoyaltyTransaction::class,
            ))
            ->setParameter('account', $account)
            ->getSingleScalarResult();

        $account->setBalance($balance);
        $account->setLifetimeEarned($lifetimeEarned);
    }
}
