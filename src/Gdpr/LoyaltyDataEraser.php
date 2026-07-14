<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Gdpr;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Sylius\Component\Core\Model\CustomerInterface;

final class LoyaltyDataEraser implements LoyaltyDataEraserInterface
{
    use ORMTrait;

    /**
     * @param class-string<LoyaltyAccountInterface> $accountClass
     * @param class-string<LoyaltyTransactionInterface> $transactionClass
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly LoyaltyAccountRepositoryInterface $accountRepository,
        private readonly string $accountClass,
        private readonly string $transactionClass,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function erase(CustomerInterface $customer): int
    {
        $accounts = $this->accountRepository->findByCustomer($customer);
        if ([] === $accounts) {
            return 0;
        }

        $manager = $this->getManager($this->accountClass);

        // Wipe the ledger first (transaction.account is non-nullable), then the accounts themselves.
        // Bulk DQL keeps this DB-agnostic rather than leaning on the mapping's ON DELETE CASCADE.
        $manager->createQuery(sprintf('DELETE FROM %s t WHERE t.account IN (:accounts)', $this->transactionClass))
            ->setParameter('accounts', $accounts)
            ->execute()
        ;
        $manager->createQuery(sprintf('DELETE FROM %s a WHERE a.customer = :customer', $this->accountClass))
            ->setParameter('customer', $customer)
            ->execute()
        ;

        // Bulk deletes bypass the unit of work, so drop the now-stale entities to avoid resurrecting them.
        foreach ($accounts as $account) {
            $manager->detach($account);
        }

        return count($accounts);
    }
}
