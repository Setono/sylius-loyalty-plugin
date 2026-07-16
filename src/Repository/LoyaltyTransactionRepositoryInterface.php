<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Repository;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<LoyaltyTransactionInterface>
 */
interface LoyaltyTransactionRepositoryInterface extends RepositoryInterface
{
    /**
     * The account's most recent ledger rows, newest first (by occurrence, then id for a stable order).
     *
     * @return list<LoyaltyTransactionInterface>
     */
    public function findLatestByAccount(LoyaltyAccountInterface $account, int $limit): array;

    /**
     * The account's complete ledger in chronological order (oldest first) — used by the ledger inspector.
     *
     * @return list<LoyaltyTransactionInterface>
     */
    public function findByAccount(LoyaltyAccountInterface $account): array;

    public function countByAccount(LoyaltyAccountInterface $account): int;

    /**
     * The points earned (order + action credits) at or after $since — for admin reporting.
     */
    public function sumEarnedSince(\DateTimeInterface $since): int;

    /**
     * The points redeemed (as a positive number) at or after $since — for admin reporting.
     */
    public function sumRedeemedSince(\DateTimeInterface $since): int;
}
