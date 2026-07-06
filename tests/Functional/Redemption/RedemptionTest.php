<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional\Redemption;

use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\LoyaltyAdjustmentTypes;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Redemption\AppliedPointsProviderInterface;
use Setono\SyliusLoyaltyPlugin\Tests\Application\Entity\Order;
use Setono\SyliusLoyaltyPlugin\Tests\Functional\FunctionalTestCase;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;

final class RedemptionTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_applies_a_clamped_redemption_and_debits_the_applied_points_at_completion(): void
    {
        $container = self::getContainer();
        $entityManager = $this->entityManager();

        // Any variant priced in a channel, straight from the fixtures
        $channelPricing = $entityManager->getRepository(\Sylius\Component\Core\Model\ChannelPricing::class)->findOneBy([]);
        \assert($channelPricing instanceof ChannelPricingInterface);
        $variant = $channelPricing->getProductVariant();
        \assert($variant instanceof ProductVariantInterface);
        $channel = $entityManager->getRepository(\Sylius\Component\Core\Model\Channel::class)
            ->findOneBy(['code' => $channelPricing->getChannelCode()]);
        \assert($channel instanceof ChannelInterface);

        // A customer with 1000 points
        $customer = $this->customer();
        $accountProvider = $container->get(LoyaltyAccountProviderInterface::class);
        \assert($accountProvider instanceof LoyaltyAccountProviderInterface);
        $account = $accountProvider->getByCustomerAndChannel($customer, $channel);
        $ledger = $container->get(LoyaltyLedgerInterface::class);
        \assert($ledger instanceof LoyaltyLedgerInterface);
        $ledger->manualCredit($account, 1000, 'goodwill', 'Redemption test seed');

        // A cart with a huge "Use max"-style request
        $order = $this->cart($channel, $customer, $variant);
        $order->setLoyaltyPointsRequested(100000);

        $processor = $container->get('sylius.order_processing.order_processor');
        \assert($processor instanceof OrderProcessorInterface);
        $processor->process($order);

        // Default program: conversion 1pt = 1 minor unit, cap 50% of the items total
        $expectedApplied = min(1000, (int) floor($order->getItemsTotal() * 0.5));

        self::assertGreaterThan(0, $expectedApplied);
        self::assertSame(
            -$expectedApplied,
            $order->getAdjustmentsTotalRecursively(LoyaltyAdjustmentTypes::REDEMPTION),
        );

        $appliedPointsProvider = $container->get(AppliedPointsProviderInterface::class);
        \assert($appliedPointsProvider instanceof AppliedPointsProviderInterface);
        self::assertSame($expectedApplied, $appliedPointsProvider->getAppliedPoints($order));

        // The stored request is never overwritten by clamping
        self::assertSame(100000, $order->getLoyaltyPointsRequested());

        $entityManager->persist($order);
        $entityManager->flush();

        // The completion debit uses the applied points, never the raw request; replays no-op
        $debit = $ledger->redeem($order, $expectedApplied);
        self::assertNotNull($debit);
        self::assertSame(-$expectedApplied, $debit->getPoints());
        self::assertSame(1000 - $expectedApplied, $this->reloadAccount($account)->getBalance());

        self::assertNull($ledger->redeem($order, $expectedApplied));

        // Cancelling restores the exact number of points as a new lot
        $rollback = $ledger->rollbackRedeem($order);
        self::assertNotNull($rollback);
        self::assertSame($expectedApplied, $rollback->getPoints());
        self::assertSame(1000, $this->reloadAccount($account)->getBalance());
        // Restored points were already counted when earned
        self::assertSame(1000, $this->reloadAccount($account)->getLifetimeEarned());

        self::assertNull($ledger->rollbackRedeem($order));
    }

    private function customer(): CustomerInterface
    {
        $customerFactory = self::getContainer()->get('sylius.factory.customer');
        \assert(is_object($customerFactory) && method_exists($customerFactory, 'createNew'));

        $customer = $customerFactory->createNew();
        \assert($customer instanceof CustomerInterface);
        $customer->setEmail(sprintf('redemption-%s@example.com', uniqid()));

        $this->entityManager()->persist($customer);
        $this->entityManager()->flush();

        return $customer;
    }

    private function cart(ChannelInterface $channel, CustomerInterface $customer, ProductVariantInterface $variant): Order
    {
        $container = self::getContainer();

        $orderFactory = $container->get('sylius.factory.order');
        \assert(is_object($orderFactory) && method_exists($orderFactory, 'createNew'));
        $order = $orderFactory->createNew();
        \assert($order instanceof Order);

        $order->setChannel($channel);
        $order->setCustomer($customer);
        $order->setCurrencyCode((string) $channel->getBaseCurrency()?->getCode());
        $order->setLocaleCode((string) $channel->getDefaultLocale()?->getCode());

        $orderItemFactory = $container->get('sylius.factory.order_item');
        \assert(is_object($orderItemFactory) && method_exists($orderItemFactory, 'createNew'));
        $item = $orderItemFactory->createNew();
        \assert($item instanceof OrderItemInterface);
        $item->setVariant($variant);

        $quantityModifier = $container->get('sylius.order_item_quantity_modifier');
        \assert($quantityModifier instanceof OrderItemQuantityModifierInterface);
        $quantityModifier->modify($item, 2);

        $order->addItem($item);
        \assert($order->getState() === OrderInterface::STATE_CART);

        return $order;
    }

    private function reloadAccount(LoyaltyAccountInterface $account): LoyaltyAccountInterface
    {
        $reloaded = $this->entityManager()->find($account::class, $account->getId());
        \assert($reloaded instanceof LoyaltyAccountInterface);

        return $reloaded;
    }
}
