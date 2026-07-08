<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Setono\SyliusLoyaltyPlugin\Event\AwardingPoints;
use Setono\SyliusLoyaltyPlugin\Event\PointsEarned;
use Setono\SyliusLoyaltyPlugin\Exception\RuntimeException;
use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class LoyaltyLedgerTest extends KernelTestCase
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
    public function it_appends_an_earn_action_and_recomputes_the_cached_balance(): void
    {
        $account = $this->createAccount('recompute@example.com');

        $this->ledger->earnForAction($account, 'newsletter-signup', 150);

        $this->manager->clear();
        $reloaded = $this->reloadAccount($account);

        self::assertSame(150, $reloaded->getBalance());
        self::assertSame(150, $reloaded->getLifetimeEarned());
        self::assertCount(1, $this->transactionsFor($reloaded));
    }

    /**
     * @test
     */
    public function it_is_an_idempotent_no_op_when_the_same_action_is_delivered_twice(): void
    {
        $account = $this->createAccount('idempotent@example.com');

        $this->ledger->earnForAction($account, 'newsletter-signup', 150);
        $this->ledger->earnForAction($account, 'newsletter-signup', 150);

        $this->manager->clear();
        $reloaded = $this->reloadAccount($account);

        self::assertSame(150, $reloaded->getBalance(), 'The duplicate delivery must not double the balance');
        self::assertCount(1, $this->transactionsFor($reloaded));
    }

    /**
     * @test
     */
    public function a_listener_can_cancel_the_award_before_it_is_written(): void
    {
        $this->eventDispatcher()->addListener(
            AwardingPoints::class,
            static function (AwardingPoints $event): void {
                $event->cancelled = true;
            },
        );

        $account = $this->createAccount('cancelled@example.com');

        $this->ledger->earnForAction($account, 'newsletter-signup', 150);

        $this->manager->clear();
        $reloaded = $this->reloadAccount($account);

        self::assertSame(0, $reloaded->getBalance());
        self::assertCount(0, $this->transactionsFor($reloaded));
    }

    /**
     * @test
     */
    public function a_listener_can_adjust_the_points_before_they_are_written(): void
    {
        $this->eventDispatcher()->addListener(
            AwardingPoints::class,
            static function (AwardingPoints $event): void {
                $event->points *= 2;
            },
        );

        $account = $this->createAccount('doubled@example.com');

        $this->ledger->earnForAction($account, 'newsletter-signup', 150);

        $this->manager->clear();
        $reloaded = $this->reloadAccount($account);

        self::assertSame(300, $reloaded->getBalance());
    }

    /**
     * @test
     */
    public function it_appends_an_earn_order_with_its_order_basis_and_rules_breakdown(): void
    {
        $awardedAccount = null;
        $earnedTransaction = null;
        $this->eventDispatcher()->addListener(AwardingPoints::class, static function (AwardingPoints $event) use (&$awardedAccount): void {
            $awardedAccount = $event->account;
        });
        $this->eventDispatcher()->addListener(PointsEarned::class, static function (PointsEarned $event) use (&$earnedTransaction): void {
            $earnedTransaction = $event->transaction;
        });

        $account = $this->createAccount('earn-order@example.com');
        $order = $this->createOrder('earn-order');

        $this->ledger->earnForOrder($account, $order, 200, 5000, [1 => 200]);

        self::assertSame($account, $awardedAccount);
        self::assertInstanceOf(EarnOrderLoyaltyTransaction::class, $earnedTransaction);

        $orderId = $order->getId();
        $this->manager->clear();
        $reloaded = $this->reloadAccount($account);

        self::assertSame(200, $reloaded->getBalance());

        $transaction = $this->manager->getRepository(EarnOrderLoyaltyTransaction::class)->findOneBy(['account' => $reloaded]);
        self::assertNotNull($transaction);
        self::assertSame(5000, $transaction->getBasisAmount());
        self::assertSame([1 => 200], $transaction->getRulesBreakdown());
        self::assertSame($orderId, $transaction->getOrder()?->getId());
    }

    /**
     * @test
     */
    public function it_awards_order_points_only_once_per_order(): void
    {
        $account = $this->createAccount('earn-order-idempotent@example.com');
        $order = $this->createOrder('earn-order-idempotent');

        $this->ledger->earnForOrder($account, $order, 200);
        $this->ledger->earnForOrder($account, $order, 200);

        $this->manager->clear();
        $reloaded = $this->reloadAccount($account);

        self::assertSame(200, $reloaded->getBalance());
        self::assertCount(1, $this->manager->getRepository(EarnOrderLoyaltyTransaction::class)->findBy(['account' => $reloaded]));
    }

    /**
     * @test
     */
    public function it_refuses_to_write_to_an_unpersisted_account(): void
    {
        $this->expectException(RuntimeException::class);

        $this->ledger->earnForAction(new LoyaltyAccount(), 'newsletter-signup', 150);
    }

    private function createAccount(string $email): LoyaltyAccountInterface
    {
        $channel = $this->createChannel('web-' . $email);

        $customer = $this->factory('sylius.factory.customer')->createNew();
        \assert($customer instanceof CustomerInterface);
        $customer->setEmail($email);
        $this->manager->persist($customer);

        $account = $this->factory('setono_sylius_loyalty.factory.account')->createNew();
        \assert($account instanceof LoyaltyAccountInterface);
        $account->setCustomer($customer);
        $account->setChannel($channel);
        $this->manager->persist($account);

        $this->manager->flush();

        return $account;
    }

    private function createChannel(string $code): ChannelInterface
    {
        $currency = $this->getOrCreateCurrency('USD');
        $locale = $this->getOrCreateLocale('en_US');

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

    private function eventDispatcher(): EventDispatcherInterface
    {
        $dispatcher = self::getContainer()->get('event_dispatcher');
        \assert($dispatcher instanceof EventDispatcherInterface);

        return $dispatcher;
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

    /**
     * @return ObjectRepository<ResourceInterface>
     */
    private function repository(string $id): ObjectRepository
    {
        $repository = self::getContainer()->get($id);
        \assert($repository instanceof ObjectRepository);

        return $repository;
    }

    private function getOrCreateCurrency(string $code): CurrencyInterface
    {
        $currency = $this->repository('sylius.repository.currency')->findOneBy(['code' => $code]);
        if ($currency instanceof CurrencyInterface) {
            return $currency;
        }

        $currency = $this->factory('sylius.factory.currency')->createNew();
        \assert($currency instanceof CurrencyInterface);
        $currency->setCode($code);
        $this->manager->persist($currency);

        return $currency;
    }

    private function getOrCreateLocale(string $code): LocaleInterface
    {
        $locale = $this->repository('sylius.repository.locale')->findOneBy(['code' => $code]);
        if ($locale instanceof LocaleInterface) {
            return $locale;
        }

        $locale = $this->factory('sylius.factory.locale')->createNew();
        \assert($locale instanceof LocaleInterface);
        $locale->setCode($code);
        $this->manager->persist($locale);

        return $locale;
    }

    private function reloadAccount(LoyaltyAccountInterface $account): LoyaltyAccountInterface
    {
        $reloaded = $this->manager->find(LoyaltyAccountInterface::class, (int) $account->getId());
        \assert($reloaded instanceof LoyaltyAccountInterface);

        return $reloaded;
    }

    /**
     * @return array<int, EarnActionLoyaltyTransaction>
     */
    private function transactionsFor(LoyaltyAccountInterface $account): array
    {
        return $this->manager->getRepository(EarnActionLoyaltyTransaction::class)->findBy(['account' => $account]);
    }
}
