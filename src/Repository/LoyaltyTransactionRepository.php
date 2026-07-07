<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Repository;

use Setono\SyliusLoyaltyPlugin\Model\ClawbackLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\ClawbackLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnReferralLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\ExpireLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\RedeemRollbackLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\ReferralInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\OrderInterface;
use Webmozart\Assert\Assert;

class LoyaltyTransactionRepository extends EntityRepository implements LoyaltyTransactionRepositoryInterface
{
    public function findForReplay(LoyaltyAccountInterface $account): array
    {
        /** @var list<LoyaltyTransactionInterface> $transactions */
        $transactions = $this->createQueryBuilder('t')
            ->andWhere('t.account = :account')
            ->setParameter('account', $account)
            ->orderBy('t.occurredAt', 'ASC')
            ->addOrderBy('t.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $transactions;
    }

    public function findEarnOrderTransaction(OrderInterface $order): ?EarnOrderLoyaltyTransactionInterface
    {
        $transaction = $this->getEntityManager()->createQueryBuilder()
            ->select('t')
            ->from(EarnOrderLoyaltyTransaction::class, 't')
            ->andWhere('t.order = :order')
            ->setParameter('order', $order)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        Assert::nullOrIsInstanceOf($transaction, EarnOrderLoyaltyTransactionInterface::class);

        return $transaction;
    }

    public function findRedeemTransaction(OrderInterface $order): ?RedeemLoyaltyTransactionInterface
    {
        $transaction = $this->getEntityManager()->createQueryBuilder()
            ->select('t')
            ->from(RedeemLoyaltyTransaction::class, 't')
            ->andWhere('t.order = :order')
            ->setParameter('order', $order)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        Assert::nullOrIsInstanceOf($transaction, RedeemLoyaltyTransactionInterface::class);

        return $transaction;
    }

    public function hasRollback(RedeemLoyaltyTransactionInterface $redeem): bool
    {
        return null !== $this->getEntityManager()->createQueryBuilder()
            ->select('r.id')
            ->from(RedeemRollbackLoyaltyTransaction::class, 'r')
            ->andWhere('r.redeem = :redeem')
            ->setParameter('redeem', $redeem)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findAccountIdsWithExpiredOpenLots(\DateTimeImmutable $now, int $limit, int $offset = 0): array
    {
        /** @var list<array{accountId: int}> $rows */
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(c.account) AS accountId')
            ->distinct()
            ->from(CreditLoyaltyTransaction::class, 'c')
            ->andWhere('c.expiresAt IS NOT NULL')
            ->andWhere('c.expiresAt < :now')
            ->andWhere(sprintf(
                'NOT EXISTS (SELECT 1 FROM %s e WHERE e.lot = c)',
                ExpireLoyaltyTransaction::class,
            ))
            ->setParameter('now', $now)
            ->orderBy('accountId', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getScalarResult()
        ;

        return array_map(static fn (array $row): int => (int) $row['accountId'], $rows);
    }

    public function sumPoints(LoyaltyAccountInterface $account): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COALESCE(SUM(t.points), 0)')
            ->andWhere('t.account = :account')
            ->setParameter('account', $account)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findHistoryPage(LoyaltyAccountInterface $account, int $page, int $limit): array
    {
        /** @var list<LoyaltyTransactionInterface> $transactions */
        $transactions = $this->createQueryBuilder('t')
            ->andWhere('t.account = :account')
            ->andWhere('t.points != 0')
            ->setParameter('account', $account)
            ->orderBy('t.occurredAt', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setFirstResult(max(0, $page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        return $transactions;
    }

    public function countHistory(LoyaltyAccountInterface $account): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.account = :account')
            ->andWhere('t.points != 0')
            ->setParameter('account', $account)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function sumPointsNewerThan(LoyaltyAccountInterface $account, LoyaltyTransactionInterface $transaction): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COALESCE(SUM(t.points), 0)')
            ->andWhere('t.account = :account')
            ->andWhere('t.occurredAt > :occurredAt OR (t.occurredAt = :occurredAt AND t.id > :id)')
            ->setParameter('account', $account)
            ->setParameter('occurredAt', $transaction->getOccurredAt())
            ->setParameter('id', $transaction->getId())
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findEarnReferralTransactions(ReferralInterface $referral): array
    {
        /** @var list<EarnReferralLoyaltyTransaction> $transactions */
        $transactions = $this->getEntityManager()
            ->getRepository(EarnReferralLoyaltyTransaction::class)
            ->findBy(['referral' => $referral])
        ;

        return $transactions;
    }

    public function findClawbackForEarn(CreditLoyaltyTransactionInterface $earn): ?ClawbackLoyaltyTransactionInterface
    {
        $clawback = $this->getEntityManager()
            ->getRepository(ClawbackLoyaltyTransaction::class)
            ->findOneBy(['earn' => $earn])
        ;
        \assert(null === $clawback || $clawback instanceof ClawbackLoyaltyTransactionInterface);

        return $clawback;
    }
}
