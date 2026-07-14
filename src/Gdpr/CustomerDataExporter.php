<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Gdpr;

use Setono\SyliusLoyaltyPlugin\Model\ClawbackLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\ManualLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class CustomerDataExporter implements CustomerDataExporterInterface
{
    public function __construct(
        private readonly LoyaltyAccountRepositoryInterface $accountRepository,
        private readonly LoyaltyTransactionRepositoryInterface $transactionRepository,
    ) {
    }

    /**
     * @return array{
     *     customer: array<string, mixed>,
     *     accounts: list<array{
     *         channel: string|null,
     *         enabled: bool,
     *         balance: int,
     *         lifetimeEarned: int,
     *         referralCode: string|null,
     *         transactions: list<array<string, mixed>>,
     *     }>,
     * }
     */
    public function export(CustomerInterface $customer): array
    {
        $accounts = [];
        foreach ($this->accountRepository->findByCustomer($customer) as $account) {
            $transactions = [];
            foreach ($this->transactionRepository->findByAccount($account) as $transaction) {
                $transactions[] = self::normalizeTransaction($transaction);
            }

            $accounts[] = [
                'channel' => $account->getChannel()?->getCode(),
                'enabled' => $account->isEnabled(),
                'balance' => $account->getBalance(),
                'lifetimeEarned' => $account->getLifetimeEarned(),
                'referralCode' => $account->getReferralCode(),
                'transactions' => $transactions,
            ];
        }

        return [
            'customer' => [
                'id' => $customer->getId(),
                'email' => $customer->getEmail(),
                'firstName' => $customer->getFirstName(),
                'lastName' => $customer->getLastName(),
            ],
            'accounts' => $accounts,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function normalizeTransaction(LoyaltyTransactionInterface $transaction): array
    {
        $data = [
            'type' => $transaction::getType(),
            'points' => $transaction->getPoints(),
            'occurredAt' => $transaction->getOccurredAt()?->format(\DateTimeInterface::ATOM),
        ];

        if ($transaction instanceof CreditLoyaltyTransactionInterface) {
            $data['expiresAt'] = $transaction->getExpiresAt()?->format(\DateTimeInterface::ATOM);
        }

        $order = self::resolveOrder($transaction);
        if ($order !== null) {
            $data['order'] = $order->getNumber() ?? (string) $order->getId();
        }

        if ($transaction instanceof EarnActionLoyaltyTransactionInterface) {
            $data['source'] = $transaction->getSourceIdentifier();
        }

        if ($transaction instanceof ManualLoyaltyTransactionInterface) {
            $data['reason'] = $transaction->getReason();
            $data['note'] = $transaction->getNote();
        }

        return $data;
    }

    private static function resolveOrder(LoyaltyTransactionInterface $transaction): ?OrderInterface
    {
        if ($transaction instanceof EarnOrderLoyaltyTransactionInterface) {
            return $transaction->getOrder();
        }

        if ($transaction instanceof RedeemLoyaltyTransactionInterface) {
            return $transaction->getOrder();
        }

        if ($transaction instanceof ClawbackLoyaltyTransactionInterface) {
            return $transaction->getOrder();
        }

        return null;
    }
}
