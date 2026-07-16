<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Doctrine\ORM;

use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class LoyaltyTransactionRepository extends EntityRepository implements LoyaltyTransactionRepositoryInterface
{
    public function findLatestByAccount(LoyaltyAccountInterface $account, int $limit): array
    {
        $transactions = $this->createQueryBuilder('t')
            ->andWhere('t.account = :account')
            ->setParameter('account', $account)
            ->orderBy('t.occurredAt', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        Assert::isArray($transactions);
        Assert::allIsInstanceOf($transactions, LoyaltyTransactionInterface::class);

        return array_values($transactions);
    }

    public function findByAccount(LoyaltyAccountInterface $account): array
    {
        $transactions = $this->createQueryBuilder('t')
            ->andWhere('t.account = :account')
            ->setParameter('account', $account)
            ->orderBy('t.occurredAt', 'ASC')
            ->addOrderBy('t.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        Assert::isArray($transactions);
        Assert::allIsInstanceOf($transactions, LoyaltyTransactionInterface::class);

        return array_values($transactions);
    }

    public function countByAccount(LoyaltyAccountInterface $account): int
    {
        $count = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.account = :account')
            ->setParameter('account', $account)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        Assert::integerish($count);

        return (int) $count;
    }

    public function sumEarnedSince(\DateTimeInterface $since): int
    {
        $qb = $this->createQueryBuilder('t');
        $sum = $qb
            ->select('COALESCE(SUM(t.points), 0)')
            ->andWhere($qb->expr()->orX(
                't INSTANCE OF ' . EarnOrderLoyaltyTransaction::class,
                't INSTANCE OF ' . EarnActionLoyaltyTransaction::class,
            ))
            ->andWhere('t.occurredAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        Assert::integerish($sum);

        return (int) $sum;
    }

    public function sumRedeemedSince(\DateTimeInterface $since): int
    {
        $sum = $this->createQueryBuilder('t')
            ->select('COALESCE(SUM(t.points), 0)')
            ->andWhere('t INSTANCE OF ' . RedeemLoyaltyTransaction::class)
            ->andWhere('t.occurredAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        Assert::integerish($sum);

        // Redemptions are stored as negative debits; report the redeemed amount as a positive number.
        return -1 * (int) $sum;
    }
}
