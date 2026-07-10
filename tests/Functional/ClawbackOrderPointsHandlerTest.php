<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Message\Command\ClawbackOrderPoints;
use Setono\SyliusLoyaltyPlugin\Model\ClawbackLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

final class ClawbackOrderPointsHandlerTest extends KernelTestCase
{
    private EntityManagerInterface $manager;

    private LoyaltyLedgerInterface $ledger;

    protected function setUp(): void
    {
        self::bootKernel();

        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($manager instanceof EntityManagerInterface);
        $this->manager = $manager;

        $ledger = self::getContainer()->get(\Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedger::class);
        \assert($ledger instanceof LoyaltyLedgerInterface);
        $this->ledger = $ledger;
    }

    /**
     * @test
     */
    public function handling_the_message_reverses_the_orders_earned_points(): void
    {
        $channel = $this->createChannel('web-clawback-msg');
        $customer = $this->createCustomer('clawback-msg@example.com');
        $account = $this->createAccount($customer, $channel);
        $order = $this->createOrder($channel, $customer);
        $this->ledger->earnForOrder($account, $order, 150);

        $this->bus()->dispatch(new ClawbackOrderPoints($order));

        self::assertSame(0, $this->reloadBalance($account));
        self::assertCount(1, $this->manager->getRepository(ClawbackLoyaltyTransaction::class)->findBy(['account' => $account]));
    }

    /**
     * @test
     */
    public function a_message_for_a_missing_order_is_a_no_op(): void
    {
        $this->bus()->dispatch(new ClawbackOrderPoints(999999));

        self::assertCount(0, $this->manager->getRepository(ClawbackLoyaltyTransaction::class)->findAll());
    }

    private function bus(): MessageBusInterface
    {
        $bus = self::getContainer()->get('setono_sylius_loyalty.command_bus');
        \assert($bus instanceof MessageBusInterface);

        return $bus;
    }

    private function reloadBalance(LoyaltyAccountInterface $account): int
    {
        $id = $account->getId();
        \assert(null !== $id);
        $this->manager->clear();

        $reloaded = $this->manager->find(LoyaltyAccountInterface::class, $id);
        \assert($reloaded instanceof LoyaltyAccountInterface);

        return $reloaded->getBalance();
    }

    private function createAccount(CustomerInterface $customer, ChannelInterface $channel): LoyaltyAccountInterface
    {
        $account = $this->factory('setono_sylius_loyalty.factory.account')->createNew();
        \assert($account instanceof LoyaltyAccountInterface);
        $account->setCustomer($customer);
        $account->setChannel($channel);
        $this->manager->persist($account);
        $this->manager->flush();

        return $account;
    }

    private function createOrder(ChannelInterface $channel, CustomerInterface $customer): OrderInterface
    {
        $order = $this->factory('sylius.factory.order')->createNew();
        \assert($order instanceof OrderInterface);
        $order->setChannel($channel);
        $order->setCustomer($customer);
        $order->setCurrencyCode('USD');
        $order->setLocaleCode('en_US');
        $order->setTokenValue('token-clawback-msg');
        $this->manager->persist($order);
        $this->manager->flush();

        return $order;
    }

    private function createChannel(string $code): ChannelInterface
    {
        $currency = $this->getOrCreateByCode('sylius.repository.currency', 'sylius.factory.currency', 'USD', CurrencyInterface::class);
        \assert($currency instanceof CurrencyInterface);
        $locale = $this->getOrCreateByCode('sylius.repository.locale', 'sylius.factory.locale', 'en_US', LocaleInterface::class);
        \assert($locale instanceof LocaleInterface);

        $channel = $this->factory('sylius.factory.channel')->createNew();
        \assert($channel instanceof ChannelInterface);
        $channel->setCode($code);
        $channel->setName('Web');
        $channel->setBaseCurrency($currency);
        $channel->setDefaultLocale($locale);
        $channel->addCurrency($currency);
        $channel->addLocale($locale);
        $this->manager->persist($channel);
        $this->manager->flush();

        return $channel;
    }

    private function createCustomer(string $email): CustomerInterface
    {
        $customer = $this->factory('sylius.factory.customer')->createNew();
        \assert($customer instanceof CustomerInterface);
        $customer->setEmail($email);
        $this->manager->persist($customer);
        $this->manager->flush();

        return $customer;
    }

    private function getOrCreateByCode(string $repositoryId, string $factoryId, string $code, string $type): ResourceInterface
    {
        $repository = self::getContainer()->get($repositoryId);
        \assert($repository instanceof ObjectRepository);

        $resource = $repository->findOneBy(['code' => $code]);
        if ($resource instanceof $type) {
            \assert($resource instanceof ResourceInterface);

            return $resource;
        }

        $resource = $this->factory($factoryId)->createNew();
        \assert(method_exists($resource, 'setCode'));
        $resource->setCode($code);
        $this->manager->persist($resource);

        return $resource;
    }

    /**
     * @return FactoryInterface<ResourceInterface>
     */
    private function factory(string $id): FactoryInterface
    {
        $factory = self::getContainer()->get($id);
        \assert($factory instanceof FactoryInterface);

        return $factory;
    }
}
