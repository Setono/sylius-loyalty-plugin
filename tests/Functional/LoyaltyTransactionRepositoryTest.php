<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LoyaltyTransactionRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $manager;

    private LoyaltyTransactionRepositoryInterface $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($manager instanceof EntityManagerInterface);
        $this->manager = $manager;

        $repository = self::getContainer()->get('setono_sylius_loyalty.repository.transaction');
        \assert($repository instanceof LoyaltyTransactionRepositoryInterface);
        $this->repository = $repository;
    }

    /**
     * @test
     */
    public function it_returns_the_latest_transactions_newest_first_within_the_limit(): void
    {
        $account = $this->createAccount('history-order', 'order@example.com');
        $this->createTransaction($account, 100, '2026-01-01 10:00:00');
        $this->createTransaction($account, 200, '2026-01-02 10:00:00');
        $this->createTransaction($account, 300, '2026-01-03 10:00:00');

        $this->manager->clear();
        $account = $this->reload($account);

        $latest = $this->repository->findLatestByAccount($account, 2);

        self::assertCount(2, $latest);
        self::assertSame(300, $latest[0]->getPoints());
        self::assertSame(200, $latest[1]->getPoints());
    }

    /**
     * @test
     */
    public function it_counts_the_transactions_of_an_account(): void
    {
        $account = $this->createAccount('history-count', 'count@example.com');
        $this->createTransaction($account, 100, '2026-01-01 10:00:00');
        $this->createTransaction($account, 200, '2026-01-02 10:00:00');

        $this->manager->clear();
        $account = $this->reload($account);

        self::assertSame(2, $this->repository->countByAccount($account));
    }

    /**
     * @test
     */
    public function it_returns_nothing_for_an_account_without_transactions(): void
    {
        $account = $this->createAccount('history-empty', 'empty@example.com');

        self::assertCount(0, $this->repository->findLatestByAccount($account, 50));
        self::assertSame(0, $this->repository->countByAccount($account));
    }

    private function createAccount(string $channelCode, string $email): LoyaltyAccountInterface
    {
        $account = new LoyaltyAccount();
        $account->setCustomer($this->createCustomer($email));
        $account->setChannel($this->createChannel($channelCode));
        $this->manager->persist($account);
        $this->manager->flush();

        return $account;
    }

    private function createTransaction(LoyaltyAccountInterface $account, int $points, string $occurredAt): void
    {
        $transaction = new EarnActionLoyaltyTransaction();
        $transaction->setAccount($account);
        $transaction->setPoints($points);
        $transaction->setOccurredAt(new \DateTimeImmutable($occurredAt));
        $this->manager->persist($transaction);
        $this->manager->flush();
    }

    private function reload(LoyaltyAccountInterface $account): LoyaltyAccountInterface
    {
        $reloaded = $this->manager->find(LoyaltyAccount::class, (int) $account->getId());
        \assert($reloaded instanceof LoyaltyAccountInterface);

        return $reloaded;
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
