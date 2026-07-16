<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Doctrine\ORM;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;
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
}
