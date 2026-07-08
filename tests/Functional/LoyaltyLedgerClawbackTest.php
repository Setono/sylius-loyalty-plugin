<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Setono\SyliusLoyaltyPlugin\Event\ClawingBackPoints;
use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Model\ClawbackLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Model\ManualDebitLoyaltyTransaction;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class LoyaltyLedgerClawbackTest extends KernelTestCase
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
    public function it_reverses_the_points_earned_for_an_order(): void
    {
        $account = $this->createAccount('clawback-full@example.com');
        $order = $this->createOrder('clawback-full');
        $this->ledger->earnForOrder($account, $order, 150);

        $this->ledger->clawback($order);

        self::assertSame(0, $this->reloadBalance($account));
        self::assertSame(-150, $this->latestClawbackPoints($account));
    }

    /**
     * @test
     */
    public function clamp_to_zero_never_drives_the_balance_negative(): void
    {
        $account = $this->createAccount('clawback-clamp@example.com');
        $order = $this->createOrder('clawback-clamp');
        $this->ledger->earnForOrder($account, $order, 150);
        $this->spend($account, 100);

        $this->ledger->clawback($order, LoyaltyProgramInterface::CLAWBACK_POLICY_CLAMP_TO_ZERO);

        self::assertSame(0, $this->reloadBalance($account));
        self::assertSame(-50, $this->latestClawbackPoints($account));
    }

    /**
     * @test
     */
    public function allow_negative_reverses_the_full_earn(): void
    {
        $account = $this->createAccount('clawback-negative@example.com');
        $order = $this->createOrder('clawback-negative');
        $this->ledger->earnForOrder($account, $order, 150);
        $this->spend($account, 100);

        $this->ledger->clawback($order, LoyaltyProgramInterface::CLAWBACK_POLICY_ALLOW_NEGATIVE);

        self::assertSame(-100, $this->reloadBalance($account));
        self::assertSame(-150, $this->latestClawbackPoints($account));
    }

    /**
     * @test
     */
    public function it_writes_only_one_clawback_per_earn(): void
    {
        $account = $this->createAccount('clawback-idempotent@example.com');
        $order = $this->createOrder('clawback-idempotent');
        $this->ledger->earnForOrder($account, $order, 150);

        $this->ledger->clawback($order);
        $this->ledger->clawback($order);

        self::assertSame(0, $this->reloadBalance($account));
        self::assertCount(1, $this->manager->getRepository(ClawbackLoyaltyTransaction::class)->findBy(['account' => $account]));
    }

    /**
     * @test
     */
    public function a_listener_can_cancel_the_clawback_before_it_is_written(): void
    {
        $this->eventDispatcher()->addListener(ClawingBackPoints::class, static function (ClawingBackPoints $event): void {
            $event->cancelled = true;
        });

        $account = $this->createAccount('clawback-cancelled@example.com');
        $order = $this->createOrder('clawback-cancelled');
        $this->ledger->earnForOrder($account, $order, 150);

        $this->ledger->clawback($order);

        self::assertSame(150, $this->reloadBalance($account));
        self::assertCount(0, $this->manager->getRepository(ClawbackLoyaltyTransaction::class)->findBy(['account' => $account]));
    }

    private function spend(LoyaltyAccountInterface $account, int $amount): void
    {
        $debit = new ManualDebitLoyaltyTransaction();
        $debit->setAccount($account);
        $debit->setPoints(-$amount);
        $debit->setOccurredAt(new \DateTimeImmutable());
        $this->manager->persist($debit);
        $account->setBalance($account->getBalance() - $amount);
        $this->manager->flush();
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

    private function latestClawbackPoints(LoyaltyAccountInterface $account): int
    {
        $clawback = $this->manager->getRepository(ClawbackLoyaltyTransaction::class)->findOneBy(['account' => $account]);
        \assert($clawback instanceof ClawbackLoyaltyTransaction);

        return $clawback->getPoints();
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
