<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

final class DashboardActionTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $manager;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($manager instanceof EntityManagerInterface);
        $this->manager = $manager;
    }

    /**
     * @test
     */
    public function it_renders_the_dashboard_stats_matching_the_repositories(): void
    {
        $channel = $this->createChannel('dashboard-web');
        $customer = $this->createCustomer('dashboard@example.com');

        $account = new LoyaltyAccount();
        $account->setCustomer($customer);
        $account->setChannel($channel);
        $account->setBalance(250);
        $account->setLifetimeEarned(300);
        $this->manager->persist($account);

        $earn = new EarnActionLoyaltyTransaction();
        $earn->setAccount($account);
        $earn->setPoints(300);
        $earn->setOccurredAt(new \DateTimeImmutable());
        $this->manager->persist($earn);

        $redeem = new RedeemLoyaltyTransaction();
        $redeem->setAccount($account);
        $redeem->setPoints(-50);
        $redeem->setOccurredAt(new \DateTimeImmutable());
        $this->manager->persist($redeem);

        $this->manager->flush();

        // Compute the expected stats through the same repositories the dashboard uses (this also
        // exercises the INSTANCE OF aggregation DQL).
        $accountRepository = self::getContainer()->get('setono_sylius_loyalty.repository.account');
        \assert($accountRepository instanceof LoyaltyAccountRepositoryInterface);
        $transactionRepository = self::getContainer()->get('setono_sylius_loyalty.repository.transaction');
        \assert($transactionRepository instanceof LoyaltyTransactionRepositoryInterface);

        $since = new \DateTimeImmutable('-30 days');
        $expectedAccounts = $accountRepository->countAll();
        $expectedOutstanding = $accountRepository->sumBalances();
        $expectedEarned = $transactionRepository->sumEarnedSince($since);
        $expectedRedeemed = $transactionRepository->sumRedeemedSince($since);

        // The data we created must have moved every needle.
        self::assertGreaterThanOrEqual(1, $expectedAccounts);
        self::assertGreaterThanOrEqual(250, $expectedOutstanding);
        self::assertGreaterThanOrEqual(300, $expectedEarned);
        self::assertGreaterThanOrEqual(50, $expectedRedeemed);

        $admin = $this->createAdminUser('dashboard-admin@example.com');
        self::assertInstanceOf(UserInterface::class, $admin);
        $this->client->loginUser($admin, 'admin');

        $this->client->request('GET', '/admin/loyalty/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-test-loyalty-stat-accounts]', (string) $expectedAccounts);
        self::assertSelectorTextContains('[data-test-loyalty-stat-outstanding]', (string) $expectedOutstanding);
        self::assertSelectorTextContains('[data-test-loyalty-stat-earned]', (string) $expectedEarned);
        self::assertSelectorTextContains('[data-test-loyalty-stat-redeemed]', (string) $expectedRedeemed);
        self::assertSelectorExists('[data-test-loyalty-accounts-link]');
    }

    private function createAdminUser(string $email): AdminUserInterface
    {
        $admin = $this->factory('sylius.factory.admin_user')->createNew();
        \assert($admin instanceof AdminUserInterface);
        $admin->setUsername($email);
        $admin->setEmail($email);
        $admin->setPlainPassword('password');
        $admin->setLocaleCode('en_US');
        $admin->setEnabled(true);
        $admin->addRole('ROLE_ADMINISTRATOR');
        $this->manager->persist($admin);
        $this->manager->flush();

        return $admin;
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
