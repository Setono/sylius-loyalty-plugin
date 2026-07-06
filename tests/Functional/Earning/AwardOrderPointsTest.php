<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional\Earning;

use Setono\SyliusLoyaltyPlugin\Message\AwardOrderPoints;
use Setono\SyliusLoyaltyPlugin\Message\Handler\AwardOrderPointsHandler;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Tests\Application\Entity\Order;
use Setono\SyliusLoyaltyPlugin\Tests\Functional\FunctionalTestCase;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\OrderCheckoutStates;
use Sylius\Component\Core\OrderPaymentStates;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;

final class AwardOrderPointsTest extends FunctionalTestCase
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

    private function channel(): ChannelInterface
    {
        $container = self::getContainer();
        $entityManager = $this->entityManager();

        $currency = $entityManager->getRepository(\Sylius\Component\Currency\Model\Currency::class)->findOneBy([]);
        \assert($currency instanceof CurrencyInterface);
        $locale = $entityManager->getRepository(\Sylius\Component\Locale\Model\Locale::class)->findOneBy([]);
        \assert($locale instanceof LocaleInterface);

        $channelFactory = $container->get('sylius.factory.channel');
        \assert(is_object($channelFactory) && method_exists($channelFactory, 'createNew'));
        $channel = $channelFactory->createNew();
        \assert($channel instanceof ChannelInterface);

        $channel->setCode(sprintf('LOYALTY_TEST_%s', uniqid()));
        $channel->setName('Loyalty test channel');
        $channel->setTaxCalculationStrategy('order_items_based');
        $channel->setBaseCurrency($currency);
        $channel->setDefaultLocale($locale);
        $channel->addCurrency($currency);
        $channel->addLocale($locale);
        $channel->setEnabled(true);

        $entityManager->persist($channel);
        $entityManager->flush();

        return $channel;
    }

    private function rule(ChannelInterface $channel, int $points, int $perAmount): void
    {
        $container = self::getContainer();

        $ruleFactory = $container->get('setono_sylius_loyalty.factory.earning_rule');
        \assert(is_object($ruleFactory) && method_exists($ruleFactory, 'createNew'));
        $rule = $ruleFactory->createNew();
        \assert($rule instanceof EarningRuleInterface);

        $rule->setName('Functional base rate');
        $rule->setChannel($channel);
        $rule->setAmountType('per_amount');
        $rule->setAmountConfiguration(['points' => $points, 'per_amount' => $perAmount]);
        $rule->setEnabled(true);

        $this->entityManager()->persist($rule);
        $this->entityManager()->flush();
    }

    private function customer(): CustomerInterface
    {
        $customerFactory = self::getContainer()->get('sylius.factory.customer');
        \assert(is_object($customerFactory) && method_exists($customerFactory, 'createNew'));

        $customer = $customerFactory->createNew();
        \assert($customer instanceof CustomerInterface);
        $customer->setEmail(sprintf('earning-%s@example.com', uniqid()));

        $this->entityManager()->persist($customer);
        $this->entityManager()->flush();

        return $customer;
    }

    private function paidOrder(ChannelInterface $channel, CustomerInterface $customer, int $unitPrice, int $quantity): Order
    {
        $container = self::getContainer();
        $entityManager = $this->entityManager();

        $variant = $entityManager->getRepository(\Sylius\Component\Core\Model\ProductVariant::class)->findOneBy([]);
        \assert($variant instanceof ProductVariantInterface);

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
        $quantityModifier->modify($item, $quantity);
        $item->setUnitPrice($unitPrice);

        $order->addItem($item);
        $order->setCheckoutState(OrderCheckoutStates::STATE_COMPLETED);
        $order->setState(Order::STATE_NEW);
        $order->setPaymentState(OrderPaymentStates::STATE_PAID);

        $entityManager->persist($order);
        $entityManager->flush();

        return $order;
    }
}
