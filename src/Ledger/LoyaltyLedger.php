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
use Setono\SyliusLoyaltyPlugin\Event\PointsEarned;
use Setono\SyliusLoyaltyPlugin\Exception\AccountNotFoundException;
use Setono\SyliusLoyaltyPlugin\Exception\RuntimeException;
use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\RedeemRollbackLoyaltyTransaction;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class LoyaltyLedger implements LoyaltyLedgerInterface, LoggerAwareInterface
{
    use ORMTrait;
    use LoggerAwareTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly EventDispatcherInterface $eventDispatcher,
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
