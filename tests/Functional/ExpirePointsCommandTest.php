<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Setono\SyliusLoyaltyPlugin\Command\ExpirePointsCommand;
use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Model\ExpireLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ExpirePointsCommandTest extends KernelTestCase
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
    public function it_expires_accounts_with_expired_lots_and_leaves_the_rest(): void
    {
        $expired = $this->createAccount('cmd-expired@example.com');
        $this->ledger->earnForAction($expired, 'past', 150, [], new \DateTimeImmutable('-1 day'));

        $active = $this->createAccount('cmd-active@example.com');
        $this->ledger->earnForAction($active, 'future', 80, [], new \DateTimeImmutable('+1 day'));

        $expiredId = $expired->getId();
        $activeId = $active->getId();
        \assert(null !== $expiredId && null !== $activeId);

        $tester = new CommandTester($this->command());
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        $this->manager->clear();
        self::assertSame(0, $this->balanceOf($expiredId));
        self::assertSame(80, $this->balanceOf($activeId));
    }

    /**
     * @test
     */
    public function it_does_not_reprocess_an_account_whose_lots_are_already_expired(): void
    {
        $account = $this->createAccount('cmd-already-expired@example.com');
        $this->ledger->earnForAction($account, 'gone', 100, [], new \DateTimeImmutable('-1 day'));
        $this->ledger->expire($account, new \DateTimeImmutable());

        (new CommandTester($this->command()))->execute([]);

        // The candidate query excludes lots that already have an expire row, so no second row is written.
        self::assertCount(1, $this->manager->getRepository(ExpireLoyaltyTransaction::class)->findBy(['account' => $account]));
    }

    private function command(): ExpirePointsCommand
    {
        $command = self::getContainer()->get(ExpirePointsCommand::class);
        \assert($command instanceof ExpirePointsCommand);

        return $command;
    }

    private function balanceOf(int $accountId): int
    {
        $account = $this->manager->find(LoyaltyAccountInterface::class, $accountId);
        \assert($account instanceof LoyaltyAccountInterface);

        return $account->getBalance();
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
