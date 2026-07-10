<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Repository;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;

interface LoyaltyTransactionRepositoryInterface
{
    /**
     * The account's most recent ledger rows, newest first (by occurrence, then id for a stable order).
     *
     * @return list<LoyaltyTransactionInterface>
     */
    public function findLatestByAccount(LoyaltyAccountInterface $account, int $limit): array;

    public function countByAccount(LoyaltyAccountInterface $account): int;
}
