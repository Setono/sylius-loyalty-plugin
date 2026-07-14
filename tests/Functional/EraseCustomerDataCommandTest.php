<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class EraseCustomerDataCommandTest extends KernelTestCase
{
    private EntityManagerInterface $manager;

    private CommandTester $commandTester;

    private LoyaltyAccountRepositoryInterface $accountRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($manager instanceof EntityManagerInterface);
        $this->manager = $manager;

        $accountRepository = self::getContainer()->get('setono_sylius_loyalty.repository.account');
        \assert($accountRepository instanceof LoyaltyAccountRepositoryInterface);
        $this->accountRepository = $accountRepository;

        $application = new Application(self::$kernel);
        $this->commandTester = new CommandTester($application->find('setono:loyalty:erase-customer-data'));
    }

    /**
     * @test
     */
    public function it_erases_the_customer_data_when_forced(): void
    {
        $customer = $this->createCustomerWithAccount('erase-force@example.com', 'erase-force-web');

        $this->commandTester->execute(['email' => 'erase-force@example.com', '--force' => true]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Erased loyalty data', $this->commandTester->getDisplay());

        $this->manager->clear();
        self::assertCount(0, $this->accountRepository->findByCustomer($this->reload($customer)));
    }

    /**
     * @test
     */
    public function it_keeps_the_data_when_the_confirmation_is_declined(): void
    {
        $customer = $this->createCustomerWithAccount('erase-keep@example.com', 'erase-keep-web');

        $this->commandTester->setInputs(['no']);
        $this->commandTester->execute(['email' => 'erase-keep@example.com']);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Aborted', $this->commandTester->getDisplay());

        $this->manager->clear();
        self::assertCount(1, $this->accountRepository->findByCustomer($this->reload($customer)));
    }

    /**
     * @test
     */
    public function it_fails_when_no_customer_matches_the_email(): void
    {
        $this->commandTester->execute(['email' => 'nobody-erase@example.com', '--force' => true]);

        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('No customer found', $this->commandTester->getDisplay());
    }

    private function createCustomerWithAccount(string $email, string $channelCode): CustomerInterface
    {
        $channel = $this->createChannel($channelCode);
        $customer = $this->createCustomer($email);

        $account = new LoyaltyAccount();
        $account->setCustomer($customer);
        $account->setChannel($channel);
        $this->manager->persist($account);

        $transaction = new EarnActionLoyaltyTransaction();
        $transaction->setAccount($account);
        $transaction->setPoints(100);
        $transaction->setOccurredAt(new \DateTimeImmutable());
        $this->manager->persist($transaction);
        $this->manager->flush();

        return $customer;
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
