<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Repository;

use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransactionInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<LoyaltyTransactionInterface>
 */
interface LoyaltyTransactionRepositoryInterface extends RepositoryInterface
{
    /**
     * Returns the account's full ledger in replay order (occurredAt ASC, id ASC).
     *
     * @return list<LoyaltyTransactionInterface>
     */
    public function findForReplay(LoyaltyAccountInterface $account): array;

    public function findEarnOrderTransaction(OrderInterface $order): ?EarnOrderLoyaltyTransactionInterface;

    public function findRedeemTransaction(OrderInterface $order): ?RedeemLoyaltyTransactionInterface;

    public function hasRollback(RedeemLoyaltyTransactionInterface $redeem): bool;

    /**
     * Returns ids of accounts that have credit lots past their expiry not yet closed by an
     * expire transaction. Zero-point expire entries close fully consumed lots, so this
     * selection stays exact.
     *
     * @return list<int>
     */
    public function findAccountIdsWithExpiredOpenLots(\DateTimeImmutable $now, int $limit, int $offset = 0): array;

    /**
     * The signed sum of all the account's transactions — must always equal the account's
     * cached balance (ledger invariant 1).
     */
    public function sumPoints(LoyaltyAccountInterface $account): int;
}
