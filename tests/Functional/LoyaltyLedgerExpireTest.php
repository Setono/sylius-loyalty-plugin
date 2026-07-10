<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Model\ExpireLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\ManualDebitLoyaltyTransaction;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LoyaltyLedgerExpireTest extends KernelTestCase
{
    private EntityManagerInterface $manager;

    private LoyaltyLedgerInterface $ledger;

    protected function setUp(): void
    {
        self::bootKernel();

        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($manager instanceof EntityManagerInterface);
        $this->manager = $manager;

        $ledger = self::getContainer()->get('Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedger');
        \assert($ledger instanceof LoyaltyLedgerInterface);
        $this->ledger = $ledger;
    }

    /**
     * @test
     */
    public function it_expires_a_lot_past_its_expiry_date(): void
    {
        $account = $this->createAccount('expire-past@example.com');
        $this->ledger->earnForAction($account, 'past', 150, [], new \DateTimeImmutable('-1 day'));

        $this->ledger->expire($account, new \DateTimeImmutable());

        self::assertSame(0, $this->reloadBalance($account));
        self::assertSame(-150, $this->latestExpirePoints($account));
    }

    /**
     * @test
     */
    public function it_leaves_a_lot_that_has_not_yet_expired(): void
    {
        $account = $this->createAccount('expire-future@example.com');
        $this->ledger->earnForAction($account, 'future', 150, [], new \DateTimeImmutable('+1 day'));

        $this->ledger->expire($account, new \DateTimeImmutable());

        self::assertSame(150, $this->reloadBalance($account));
        self::assertCount(0, $this->manager->getRepository(ExpireLoyaltyTransaction::class)->findBy(['account' => $account]));
    }

    /**
     * @test
     */
    public function it_expires_only_the_lots_that_have_expired(): void
    {
        $account = $this->createAccount('expire-mixed@example.com');
        $this->ledger->earnForAction($account, 'gone', 100, [], new \DateTimeImmutable('-1 day'));
        $this->ledger->earnForAction($account, 'kept', 50, [], new \DateTimeImmutable('+1 day'));

        $this->ledger->expire($account, new \DateTimeImmutable());

        self::assertSame(50, $this->reloadBalance($account));
        self::assertSame(-100, $this->latestExpirePoints($account));
    }

    /**
     * @test
     */
    public function it_writes_a_zero_point_close_row_for_an_already_consumed_expired_lot(): void
    {
        $account = $this->createAccount('expire-consumed@example.com');
        $this->ledger->earnForAction($account, 'consumed', 150, [], new \DateTimeImmutable('-1 day'));
        $this->spend($account, 150);

        $this->ledger->expire($account, new \DateTimeImmutable());

        self::assertSame(0, $this->reloadBalance($account));
        self::assertSame(0, $this->latestExpirePoints($account));
    }

    /**
     * @test
     */
    public function it_is_idempotent(): void
    {
        $account = $this->createAccount('expire-idempotent@example.com');
        $this->ledger->earnForAction($account, 'twice', 150, [], new \DateTimeImmutable('-1 day'));

        $this->ledger->expire($account, new \DateTimeImmutable());
        $this->ledger->expire($account, new \DateTimeImmutable());

        self::assertSame(0, $this->reloadBalance($account));
        self::assertCount(1, $this->manager->getRepository(ExpireLoyaltyTransaction::class)->findBy(['account' => $account]));
    }

    /**
     * @test
     */
    public function it_never_expires_a_lot_without_an_expiry_date(): void
    {
        $account = $this->createAccount('expire-never@example.com');
        $this->ledger->earnForAction($account, 'never', 150);

        $this->ledger->expire($account, new \DateTimeImmutable('+10 years'));

        self::assertSame(150, $this->reloadBalance($account));
        self::assertCount(0, $this->manager->getRepository(ExpireLoyaltyTransaction::class)->findBy(['account' => $account]));
    }

    /**
     * @test
     */
    public function it_expires_only_the_remaining_points_of_a_partially_consumed_lot(): void
    {
        $account = $this->createAccount('expire-partial@example.com');
        $this->ledger->earnForAction($account, 'partial', 150, [], new \DateTimeImmutable('-1 day'));
        $this->spend($account, 50);

        $this->ledger->expire($account, new \DateTimeImmutable());

        self::assertSame(0, $this->reloadBalance($account));
        self::assertSame(-100, $this->latestExpirePoints($account));
    }

    /**
     * @test
     */
    public function it_expires_every_lot_that_has_expired(): void
    {
        $account = $this->createAccount('expire-multiple@example.com');
        $this->ledger->earnForAction($account, 'first', 100, [], new \DateTimeImmutable('-2 days'));
        $this->ledger->earnForAction($account, 'second', 60, [], new \DateTimeImmutable('-1 day'));

        $this->ledger->expire($account, new \DateTimeImmutable());

        self::assertSame(0, $this->reloadBalance($account));
        self::assertCount(2, $this->manager->getRepository(ExpireLoyaltyTransaction::class)->findBy(['account' => $account]));
    }

    /**
     * @test
     */
    public function it_expires_a_lot_whose_expiry_is_exactly_the_reference_moment(): void
    {
        $account = $this->createAccount('expire-boundary@example.com');
        $moment = new \DateTimeImmutable('2026-01-01 00:00:00');
        $this->ledger->earnForAction($account, 'boundary', 150, [], $moment);

        $this->ledger->expire($account, $moment);

        self::assertSame(0, $this->reloadBalance($account));
        self::assertSame(-150, $this->latestExpirePoints($account));
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

    private function latestExpirePoints(LoyaltyAccountInterface $account): int
    {
        $expiration = $this->manager->getRepository(ExpireLoyaltyTransaction::class)->findOneBy(['account' => $account]);
        \assert($expiration instanceof ExpireLoyaltyTransaction);

        return $expiration->getPoints();
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
