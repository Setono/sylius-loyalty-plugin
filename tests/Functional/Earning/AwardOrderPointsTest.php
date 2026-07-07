<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional\Earning;

use Setono\SyliusLoyaltyPlugin\Message\AwardOrderPoints;
use Setono\SyliusLoyaltyPlugin\Message\Handler\AwardOrderPointsHandler;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Sylius\Component\Core\OrderPaymentStates;

final class AwardOrderPointsTest extends AwardOrderPointsTestCase
{
    /**
     * @test
     */
    public function it_awards_points_once_for_a_paid_order_even_when_dispatched_twice(): void
    {
        $container = self::getContainer();
        $entityManager = $this->entityManager();

        // An isolated channel so no other rules interfere with the expected amounts
        $channel = $this->channel();
        $this->rule($channel, points: 1, perAmount: 100);

        $customer = $this->customer();
        $order = $this->paidOrder($channel, $customer, unitPrice: 5000, quantity: 2); // 100.00

        $handler = $container->get(AwardOrderPointsHandler::class);
        \assert($handler instanceof AwardOrderPointsHandler);

        $message = new AwardOrderPoints((int) $order->getId());
        $handler($message);
        // The pay transition can fire twice (winzou + workflow, redeliveries) — a no-op
        $handler($message);

        $accountProvider = $container->get(LoyaltyAccountProviderInterface::class);
        \assert($accountProvider instanceof LoyaltyAccountProviderInterface);
        $account = $accountProvider->getByCustomerAndChannel($customer, $channel);

        self::assertSame(100, $account->getBalance());
        self::assertSame(100, $account->getLifetimeEarned());

        $transactionRepository = $container->get(LoyaltyTransactionRepositoryInterface::class);
        \assert($transactionRepository instanceof LoyaltyTransactionRepositoryInterface);

        $transactions = $transactionRepository->findForReplay($account);
        self::assertCount(1, $transactions);

        $earn = $transactionRepository->findEarnOrderTransaction($order);
        self::assertNotNull($earn);
        self::assertSame(100, $earn->getPoints());
        self::assertSame(10000, $earn->getBasisAmount());
        self::assertNotNull($earn->getExpiresAt());
    }

    /**
     * @test
     */
    public function it_awards_nothing_for_an_unpaid_order(): void
    {
        $container = self::getContainer();

        $channel = $this->channel();
        $this->rule($channel, points: 1, perAmount: 100);

        $customer = $this->customer();
        $order = $this->paidOrder($channel, $customer, unitPrice: 5000, quantity: 1);
        $order->setPaymentState(OrderPaymentStates::STATE_AWAITING_PAYMENT);
        $this->entityManager()->flush();

        $handler = $container->get(AwardOrderPointsHandler::class);
        \assert($handler instanceof AwardOrderPointsHandler);
        $handler(new AwardOrderPoints((int) $order->getId()));

        $accountProvider = $container->get(LoyaltyAccountProviderInterface::class);
        \assert($accountProvider instanceof LoyaltyAccountProviderInterface);

        self::assertSame(0, $accountProvider->getByCustomerAndChannel($customer, $channel)->getBalance());
    }
}
