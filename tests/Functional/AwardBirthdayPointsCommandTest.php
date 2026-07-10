<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Setono\SyliusLoyaltyPlugin\Model\EarningRule;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class AwardBirthdayPointsCommandTest extends KernelTestCase
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
        $this->commandTester = new CommandTester($application->find('setono:loyalty:award-birthday-points'));
    }

    /**
     * @test
     */
    public function it_awards_birthday_points_to_customers_whose_birthday_is_today(): void
    {
        $channel = $this->createChannel('birthday-web');
        $customer = $this->createCustomer('birthday@example.com', new \DateTimeImmutable('2000-' . date('m-d')));
        $this->createAccount($customer, $channel);
        $this->createFixedPointsRule($channel, 'customer_birthday', 150);

        $this->commandTester->execute([]);

        $this->manager->clear();

        $account = $this->accountFor($customer, $channel);
        self::assertNotNull($account);
        self::assertSame(150, $account->getBalance());
    }

    /**
     * @test
     */
    public function it_does_not_award_when_the_birthday_is_not_today(): void
    {
        $notToday = (new \DateTimeImmutable())->modify('+1 day');

        $channel = $this->createChannel('birthday-other');
        $customer = $this->createCustomer('birthday-other@example.com', new \DateTimeImmutable('2000-' . $notToday->format('m-d')));
        $this->createAccount($customer, $channel);
        $this->createFixedPointsRule($channel, 'customer_birthday', 150);

        $this->commandTester->execute([]);

        $this->manager->clear();

        $account = $this->accountFor($customer, $channel);
        self::assertNotNull($account);
        self::assertSame(0, $account->getBalance());
    }

    private function createAccount(CustomerInterface $customer, ChannelInterface $channel): void
    {
        $account = new LoyaltyAccount();
        $account->setCustomer($customer);
        $account->setChannel($channel);
        $this->manager->persist($account);
        $this->manager->flush();
    }

    private function createFixedPointsRule(ChannelInterface $channel, string $trigger, int $points): void
    {
        $rule = new EarningRule();
        $rule->setChannel($channel);
        $rule->setEnabled(true);
        $rule->setTrigger($trigger);
        $rule->setAmountType('fixed');
        $rule->setAmountConfiguration(['points' => $points]);
        $this->manager->persist($rule);
        $this->manager->flush();
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

    private function createCustomer(string $email, \DateTimeInterface $birthday): CustomerInterface
    {
        $customer = $this->factory('sylius.factory.customer')->createNew();
        \assert($customer instanceof CustomerInterface);
        $customer->setEmail($email);
        $customer->setBirthday($birthday);
        $this->manager->persist($customer);
        $this->manager->flush();

        return $customer;
    }

    private function accountFor(CustomerInterface $customer, ChannelInterface $channel): ?LoyaltyAccountInterface
    {
        $account = $this->manager->getRepository(LoyaltyAccountInterface::class)->findOneBy([
            'customer' => $customer,
            'channel' => $channel,
        ]);
        \assert(null === $account || $account instanceof LoyaltyAccountInterface);

        return $account;
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
