<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional\Redemption;

use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\LoyaltyAdjustmentTypes;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Redemption\AppliedPointsProviderInterface;
use Setono\SyliusLoyaltyPlugin\Tests\Application\Entity\Order;
use Setono\SyliusLoyaltyPlugin\Tests\Functional\Earning\AwardOrderPointsTestCase;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;

final class RedemptionTest extends AwardOrderPointsTestCase
{
    /**
     * @test
     */
    public function it_applies_a_clamped_redemption_and_debits_the_applied_points_at_completion(): void
    {
        $container = self::getContainer();
        $entityManager = $this->entityManager();

        // An isolated channel with an explicitly priced variant: no fixture promotions or tax
        // rates can shift the totals, so the clamping math is exact on every Sylius version
        $channel = $this->channel();
        $variant = $entityManager->getRepository(\Sylius\Component\Core\Model\ProductVariant::class)->findOneBy([]);
        \assert($variant instanceof ProductVariantInterface);

        $channelPricing = new \Sylius\Component\Core\Model\ChannelPricing();
        $channelPricing->setChannelCode($channel->getCode());
        $channelPricing->setPrice(950);
        $variant->addChannelPricing($channelPricing);
        $entityManager->persist($channelPricing);
        $entityManager->flush();

        // A customer with 1000 points
        $customer = $this->customer();
        $accountProvider = $container->get(LoyaltyAccountProviderInterface::class);
        \assert($accountProvider instanceof LoyaltyAccountProviderInterface);
        $account = $accountProvider->getByCustomerAndChannel($customer, $channel);
        $ledger = $container->get(LoyaltyLedgerInterface::class);
        \assert($ledger instanceof LoyaltyLedgerInterface);
        $ledger->manualCredit($account, 1000, 'goodwill', 'Redemption test seed');

        $order = $this->cart($channel, $customer, $variant);

        $processor = $container->get('sylius.order_processing.order_processor');
        \assert($processor instanceof OrderProcessorInterface);

        // A huge "Use max"-style request
        $order->setLoyaltyPointsRequested(100000);
        $processor->process($order);

        // Default program: conversion 1pt = 1 minor unit, cap 50% of the 19.00 items total
        // (2 x 9.50) — the cap binds before the 1000-point balance does
        $expectedApplied = 950;

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
