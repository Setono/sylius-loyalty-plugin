<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Provider\Shop\CartRedemptionView;
use Setono\SyliusLoyaltyPlugin\Provider\Shop\CartRedemptionViewProviderInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class LoyaltyRuntime implements RuntimeExtensionInterface
{
    /**
     * @param class-string<LoyaltyAccountInterface> $accountClass
     */
    public function __construct(
        private readonly CartRedemptionViewProviderInterface $cartRedemptionViewProvider,
        private readonly LoyaltyTransactionRepositoryInterface $transactionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $accountClass,
    ) {
    }

    public function cartRedemption(OrderInterface $cart): ?CartRedemptionView
    {
        return $this->cartRedemptionViewProvider->getView($cart);
    }

    /**
     * The transaction's discriminator value (e.g. "earn_order", "redeem").
     */
    public function transactionType(LoyaltyTransactionInterface $transaction): string
    {
        $discriminator = $this->entityManager->getClassMetadata($transaction::class)->discriminatorValue;

        return is_string($discriminator) ? $discriminator : $transaction::class;
    }

    /**
     * @return list<LoyaltyAccountInterface>
     */
    public function accountsOf(CustomerInterface $customer): array
    {
        /** @var list<LoyaltyAccountInterface> $accounts */
        $accounts = $this->entityManager->getRepository($this->accountClass)->findBy(['customer' => $customer]);

        return $accounts;
    }

    /**
     * The account's latest transactions, newest first.
     *
     * @return list<LoyaltyTransactionInterface>
     */
    public function latestTransactions(LoyaltyAccountInterface $account, int $limit = 25): array
    {
        return array_slice(array_reverse($this->transactionRepository->findForReplay($account)), 0, $limit);
    }
}
