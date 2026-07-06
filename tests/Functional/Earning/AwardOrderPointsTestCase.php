<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional\Earning;

use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
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

abstract class AwardOrderPointsTestCase extends FunctionalTestCase
{
    protected function channel(): ChannelInterface
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

    protected function rule(ChannelInterface $channel, int $points, int $perAmount): void
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

    protected function customer(): CustomerInterface
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

    protected function paidOrder(ChannelInterface $channel, CustomerInterface $customer, int $unitPrice, int $quantity): Order
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
