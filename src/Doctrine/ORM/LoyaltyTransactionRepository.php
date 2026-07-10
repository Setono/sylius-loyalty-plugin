<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Doctrine\ORM;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Webmozart\Assert\Assert;

final class LoyaltyTransactionRepository implements LoyaltyTransactionRepositoryInterface
{
    use ORMTrait;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function findLatestByAccount(LoyaltyAccountInterface $account, int $limit): array
    {
        $transactions = $this->getManager(LoyaltyTransaction::class)->createQueryBuilder()
            ->select('t')
            ->from(LoyaltyTransaction::class, 't')
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

    public function countByAccount(LoyaltyAccountInterface $account): int
    {
        $count = $this->getManager(LoyaltyTransaction::class)->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(LoyaltyTransaction::class, 't')
            ->andWhere('t.account = :account')
            ->setParameter('account', $account)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        Assert::integerish($count);

        return (int) $count;
    }
}
