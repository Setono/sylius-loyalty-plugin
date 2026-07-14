<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
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

final class ExportCustomerDataCommandTest extends KernelTestCase
{
    private EntityManagerInterface $manager;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        self::bootKernel();

        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($manager instanceof EntityManagerInterface);
        $this->manager = $manager;

        $application = new Application(self::$kernel);
        $this->commandTester = new CommandTester($application->find('setono:loyalty:export-customer-data'));
    }

    /**
     * @test
     */
    public function it_exports_the_customer_accounts_and_ledger_as_json(): void
    {
        $channel = $this->createChannel('export-web');
        $customer = $this->createCustomer('export@example.com');

        $account = new LoyaltyAccount();
        $account->setCustomer($customer);
        $account->setChannel($channel);
        $account->setBalance(120);
        $account->setLifetimeEarned(120);
        $this->manager->persist($account);

        $transaction = new EarnActionLoyaltyTransaction();
        $transaction->setAccount($account);
        $transaction->setPoints(120);
        $transaction->setOccurredAt(new \DateTimeImmutable('2026-03-04T05:06:07+00:00'));
        $transaction->setSourceIdentifier('customer_registered:1:1');
        $this->manager->persist($transaction);
        $this->manager->flush();

        $this->commandTester->execute(['email' => 'export@example.com']);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        /** @var array{customer: array<string, mixed>, accounts: list<array{channel: mixed, balance: mixed, transactions: list<array<string, mixed>>}>} $data */
        $data = json_decode(trim($this->commandTester->getDisplay()), true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame('export@example.com', $data['customer']['email']);
        self::assertCount(1, $data['accounts']);
        self::assertSame('export-web', $data['accounts'][0]['channel']);
        self::assertSame(120, $data['accounts'][0]['balance']);
        self::assertCount(1, $data['accounts'][0]['transactions']);
        self::assertSame('earn_action', $data['accounts'][0]['transactions'][0]['type']);
        self::assertSame(120, $data['accounts'][0]['transactions'][0]['points']);
        self::assertSame('customer_registered:1:1', $data['accounts'][0]['transactions'][0]['source']);
    }

    /**
     * @test
     */
    public function it_matches_the_customer_case_insensitively(): void
    {
        $this->createCustomer('mixedcase@example.com');

        $this->commandTester->execute(['email' => 'MixedCase@Example.com']);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        /** @var array{customer: array<string, mixed>} $data */
        $data = json_decode(trim($this->commandTester->getDisplay()), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('mixedcase@example.com', $data['customer']['email']);
    }

    /**
     * @test
     */
    public function it_fails_when_no_customer_matches_the_email(): void
    {
        $this->commandTester->execute(['email' => 'nobody@example.com']);

        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('No customer found', $this->commandTester->getDisplay());
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
        $customer->setFirstName('Ex');
        $customer->setLastName('Ample');
        $this->manager->persist($customer);
        $this->manager->flush();

        return $customer;
    }

    private function getOrCreateByCode(string $repositoryId, string $factoryId, string $code, string $type): ResourceInterface
    {
        $repository = self::getContainer()->get($repositoryId);
        \assert($repository instanceof \Doctrine\Persistence\ObjectRepository);

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
