<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Controller\Shop;

use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Sylius\Component\Core\Context\ShopperContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Webmozart\Assert\Assert;

/**
 * Renders the "My loyalty" shop account page: the balance hero plus the customer's recent ledger
 * history. The account is looked up (not created) — a customer who has never earned simply sees a
 * zero state.
 */
final class ShowLoyaltyAccountAction
{
    public function __construct(
        private readonly ShopperContextInterface $shopperContext,
        private readonly LoyaltyAccountRepositoryInterface $accountRepository,
        private readonly LoyaltyTransactionRepositoryInterface $transactionRepository,
        private readonly Environment $twig,
        private readonly int $historyLimit = 50,
    ) {
    }

    public function __invoke(): Response
    {
        $customer = $this->shopperContext->getCustomer();
        Assert::isInstanceOf($customer, CustomerInterface::class);

        $channel = $this->shopperContext->getChannel();
        Assert::isInstanceOf($channel, ChannelInterface::class);

        $account = $this->accountRepository->findOneByCustomerAndChannel($customer, $channel);

        $transactions = null === $account ? [] : $this->transactionRepository->findLatestByAccount($account, $this->historyLimit);
        $total = null === $account ? 0 : $this->transactionRepository->countByAccount($account);

        return new Response($this->twig->render('@SetonoSyliusLoyaltyPlugin/shop/account/loyalty.html.twig', [
            'account' => $account,
            'transactions' => $transactions,
            'total_transactions' => $total,
            'history_limit' => $this->historyLimit,
        ]));
    }
}
