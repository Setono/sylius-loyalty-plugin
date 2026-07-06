<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional\Ledger;

use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Tests\Functional\FunctionalTestCase;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

final class LoyaltyLedgerTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_earns_action_points_exactly_once_per_source_identifier(): void
    {
        $account = $this->account();
        $ledger = $this->ledger();

        $first = $ledger->earnAction($account, 100, 'functional-test:1');

        self::assertNotNull($first);
        self::assertSame(100, $first->getPoints());
        self::assertSame(100, $account->getBalance());
        self::assertSame(100, $account->getLifetimeEarned());

        // Redelivery of the same action must be a no-op thanks to the unique constraint
        $second = $this->ledger()->earnAction($this->reloadAccount($account), 100, 'functional-test:1');

        self::assertNull($second);
        self::assertSame(100, $this->reloadAccount($account)->getBalance());
    }

    /**
     * @test
     */
    public function it_records_manual_adjustments_and_keeps_the_balance_in_sync_with_the_ledger(): void
    {
        $account = $this->account();
        $ledger = $this->ledger();

        $ledger->manualCredit($account, 500, 'goodwill', 'Functional test credit');
        $ledger->manualDebit($this->reloadAccount($account), 200, 'correction', 'Functional test debit');

        $account = $this->reloadAccount($account);
        self::assertSame(300, $account->getBalance());
        self::assertSame(500, $account->getLifetimeEarned());

        $transactionRepository = self::getContainer()->get(LoyaltyTransactionRepositoryInterface::class);
        \assert($transactionRepository instanceof LoyaltyTransactionRepositoryInterface);

        self::assertSame(300, $transactionRepository->sumPoints($account));
        self::assertCount(2, $transactionRepository->findForReplay($account));
    }

    private function account(): LoyaltyAccountInterface
    {
        $container = self::getContainer();

        $customerFactory = $container->get('sylius.factory.customer');
        \assert(is_object($customerFactory) && method_exists($customerFactory, 'createNew'));

        $customer = $customerFactory->createNew();
        \assert($customer instanceof CustomerInterface);
        $customer->setEmail(sprintf('loyalty-%s@example.com', uniqid()));

        $entityManager = $this->entityManager();
        $entityManager->persist($customer);
        $entityManager->flush();

        $channel = $entityManager->getRepository(\Sylius\Component\Core\Model\Channel::class)->findOneBy([]);
        \assert($channel instanceof ChannelInterface);

        $accountProvider = $container->get(LoyaltyAccountProviderInterface::class);
        \assert($accountProvider instanceof LoyaltyAccountProviderInterface);

        return $accountProvider->getByCustomerAndChannel($customer, $channel);
    }

    private function ledger(): LoyaltyLedgerInterface
    {
        $ledger = self::getContainer()->get(LoyaltyLedgerInterface::class);
        \assert($ledger instanceof LoyaltyLedgerInterface);

        return $ledger;
    }

    private function reloadAccount(LoyaltyAccountInterface $account): LoyaltyAccountInterface
    {
        $reloaded = $this->entityManager()->find($account::class, $account->getId());
        \assert($reloaded instanceof LoyaltyAccountInterface);

        return $reloaded;
    }
}
