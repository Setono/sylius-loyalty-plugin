<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Setono\SyliusLoyaltyPlugin\Gdpr\LoyaltyDataEraserInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LoyaltyDataEraserTest extends KernelTestCase
{
    private EntityManagerInterface $manager;

    private LoyaltyDataEraserInterface $eraser;

    private LoyaltyAccountRepositoryInterface $accountRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($manager instanceof EntityManagerInterface);
        $this->manager = $manager;

        $eraser = self::getContainer()->get(LoyaltyDataEraserInterface::class);
        \assert($eraser instanceof LoyaltyDataEraserInterface);
        $this->eraser = $eraser;

        $accountRepository = self::getContainer()->get('setono_sylius_loyalty.repository.account');
        \assert($accountRepository instanceof LoyaltyAccountRepositoryInterface);
        $this->accountRepository = $accountRepository;
    }

    /**
     * @test
     */
    public function it_erases_the_customers_accounts_and_ledger_and_leaves_others_untouched(): void
    {
        $channel = $this->createChannel('erase-web');

        $customer = $this->createCustomer('erase@example.com');
        $account = $this->createAccount($customer, $channel);
        $this->createTransaction($account, 100);
        $this->createTransaction($account, 50);

        $other = $this->createCustomer('erase-other@example.com');
        $otherAccount = $this->createAccount($other, $channel);
        $this->createTransaction($otherAccount, 25);

        // The functional DB accumulates rows across tests, so assert on the delta rather than a total.
        $transactionsBefore = $this->countTransactions();

        $erased = $this->eraser->erase($customer);

        self::assertSame(1, $erased);

        $this->manager->clear();

        self::assertCount(0, $this->accountRepository->findByCustomer($this->reload($customer)));
        self::assertCount(1, $this->accountRepository->findByCustomer($this->reload($other)));
        self::assertSame($transactionsBefore - 2, $this->countTransactions());
    }

    /**
     * @test
     */
    public function it_returns_zero_when_the_customer_has_no_loyalty_data(): void
    {
        $customer = $this->createCustomer('erase-empty@example.com');

        self::assertSame(0, $this->eraser->erase($customer));
    }

    private function countTransactions(): int
    {
        $count = $this->manager->createQuery(sprintf('SELECT COUNT(t.id) FROM %s t', LoyaltyTransaction::class))
            ->getSingleScalarResult();
        \assert(is_numeric($count));

        return (int) $count;
    }

    private function createAccount(CustomerInterface $customer, ChannelInterface $channel): LoyaltyAccountInterface
    {
        $account = new LoyaltyAccount();
        $account->setCustomer($customer);
        $account->setChannel($channel);
        $this->manager->persist($account);
        $this->manager->flush();

        return $account;
    }

    private function createTransaction(LoyaltyAccountInterface $account, int $points): void
    {
        $transaction = new EarnActionLoyaltyTransaction();
        $transaction->setAccount($account);
        $transaction->setPoints($points);
        $transaction->setOccurredAt(new \DateTimeImmutable());
        $this->manager->persist($transaction);
        $this->manager->flush();
    }

    private function reload(CustomerInterface $customer): CustomerInterface
    {
        $reloaded = $this->manager->find($customer::class, (int) $customer->getId());
        \assert($reloaded instanceof CustomerInterface);

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
