<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Setono\SyliusLoyaltyPlugin\Event\RedeemingPoints;
use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\RedeemRollbackLoyaltyTransaction;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class RedemptionLedgerTest extends KernelTestCase
{
    private EntityManagerInterface $manager;

    private LoyaltyLedgerInterface $ledger;

    protected function setUp(): void
    {
        self::bootKernel();

        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($manager instanceof EntityManagerInterface);
        $this->manager = $manager;

        $ledger = self::getContainer()->get('test.setono_sylius_loyalty.ledger');
        \assert($ledger instanceof LoyaltyLedgerInterface);
        $this->ledger = $ledger;
    }

    /**
     * @test
     */
    public function it_redeems_points_on_an_order(): void
    {
        $account = $this->accountWithBalance('redeem@example.com', 1000);
        $order = $this->createOrder('redeem');

        $this->ledger->redeem($account, $order, 300);

        self::assertSame(700, $this->reloadBalance($account));
        self::assertSame(-300, $this->latestRedeemPoints($account));
    }

    /**
     * @test
     */
    public function it_clamps_a_redemption_to_the_available_balance(): void
    {
        $account = $this->accountWithBalance('redeem-clamp@example.com', 200);
        $order = $this->createOrder('redeem-clamp');

        $this->ledger->redeem($account, $order, 500);

        self::assertSame(0, $this->reloadBalance($account));
        self::assertSame(-200, $this->latestRedeemPoints($account));
    }

    /**
     * @test
     */
    public function it_redeems_only_once_per_order(): void
    {
        $account = $this->accountWithBalance('redeem-idempotent@example.com', 1000);
        $order = $this->createOrder('redeem-idempotent');

        $this->ledger->redeem($account, $order, 300);
        $this->ledger->redeem($account, $order, 300);

        self::assertSame(700, $this->reloadBalance($account));
        self::assertCount(1, $this->manager->getRepository(RedeemLoyaltyTransaction::class)->findBy(['account' => $account]));
    }

    /**
     * @test
     */
    public function a_listener_can_cancel_the_redemption(): void
    {
        $this->eventDispatcher()->addListener(RedeemingPoints::class, static function (RedeemingPoints $event): void {
            $event->cancelled = true;
        });

        $account = $this->accountWithBalance('redeem-cancelled@example.com', 1000);
        $order = $this->createOrder('redeem-cancelled');

        $this->ledger->redeem($account, $order, 300);

        self::assertSame(1000, $this->reloadBalance($account));
        self::assertCount(0, $this->manager->getRepository(RedeemLoyaltyTransaction::class)->findBy(['account' => $account]));
    }

    /**
     * @test
     */
    public function a_listener_cannot_redeem_more_than_the_balance(): void
    {
        $this->eventDispatcher()->addListener(RedeemingPoints::class, static function (RedeemingPoints $event): void {
            $event->points = 9999;
        });

        $account = $this->accountWithBalance('redeem-overreach@example.com', 500);
        $order = $this->createOrder('redeem-overreach');

        $this->ledger->redeem($account, $order, 300);

        self::assertSame(0, $this->reloadBalance($account));
        self::assertSame(-500, $this->latestRedeemPoints($account));
    }

    /**
     * @test
     */
    public function it_rolls_back_a_redemption(): void
    {
        $account = $this->accountWithBalance('redeem-rollback@example.com', 1000);
        $order = $this->createOrder('redeem-rollback');
        $this->ledger->redeem($account, $order, 300);

        $this->ledger->rollbackRedemption($order);

        self::assertSame(1000, $this->reloadBalance($account));
        self::assertCount(1, $this->manager->getRepository(RedeemRollbackLoyaltyTransaction::class)->findBy(['account' => $account]));
    }

    /**
     * @test
     */
    public function it_rolls_back_a_redemption_only_once(): void
    {
        $account = $this->accountWithBalance('redeem-rollback-idempotent@example.com', 1000);
        $order = $this->createOrder('redeem-rollback-idempotent');
        $this->ledger->redeem($account, $order, 300);

        $this->ledger->rollbackRedemption($order);
        $this->ledger->rollbackRedemption($order);

        self::assertSame(1000, $this->reloadBalance($account));
        self::assertCount(1, $this->manager->getRepository(RedeemRollbackLoyaltyTransaction::class)->findBy(['account' => $account]));
    }

    private function accountWithBalance(string $email, int $balance): LoyaltyAccountInterface
    {
        $account = $this->createAccount($email);
        $this->ledger->earnForAction($account, 'seed', $balance);

        return $account;
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

    private function latestRedeemPoints(LoyaltyAccountInterface $account): int
    {
        $redeem = $this->manager->getRepository(RedeemLoyaltyTransaction::class)->findOneBy(['account' => $account]);
        \assert($redeem instanceof RedeemLoyaltyTransaction);

        return $redeem->getPoints();
    }

    private function createAccount(string $email): LoyaltyAccountInterface
    {
        $customer = $this->factory('sylius.factory.customer')->createNew();
        \assert($customer instanceof CustomerInterface);
        $customer->setEmail($email);
        $this->manager->persist($customer);

        $account = $this->factory('setono_sylius_loyalty.factory.account')->createNew();
        \assert($account instanceof LoyaltyAccountInterface);
        $account->setCustomer($customer);
        $account->setChannel($this->createChannel('web-' . $email));
        $this->manager->persist($account);
        $this->manager->flush();

        return $account;
    }

    private function createOrder(string $reference): OrderInterface
    {
        $order = $this->factory('sylius.factory.order')->createNew();
        \assert($order instanceof OrderInterface);
        $order->setChannel($this->createChannel('order-channel-' . $reference));
        $order->setCurrencyCode('USD');
        $order->setLocaleCode('en_US');
        $order->setTokenValue('token-' . $reference);
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

        return $channel;
    }

    private function eventDispatcher(): EventDispatcherInterface
    {
        $dispatcher = self::getContainer()->get('event_dispatcher');
        \assert($dispatcher instanceof EventDispatcherInterface);

        return $dispatcher;
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
