<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Setono\SyliusLoyaltyPlugin\Earning\ActionPointsAwarderInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\EarningRule;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleCondition;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgram;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ActionPointsAwarderTest extends KernelTestCase
{
    private EntityManagerInterface $manager;

    private ActionPointsAwarderInterface $awarder;

    protected function setUp(): void
    {
        self::bootKernel();

        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($manager instanceof EntityManagerInterface);
        $this->manager = $manager;

        $awarder = self::getContainer()->get('Setono\SyliusLoyaltyPlugin\Earning\ActionPointsAwarder');
        \assert($awarder instanceof ActionPointsAwarderInterface);
        $this->awarder = $awarder;
    }

    /**
     * @test
     */
    public function it_awards_action_points_from_the_channels_enabled_rules_for_the_trigger(): void
    {
        $channel = $this->createChannel('action-award');
        $customer = $this->createCustomer('action-award@example.com');
        $this->createFixedPointsRule($channel, 'customer_registered', 250);

        $this->awarder->award($customer, $channel, 'customer_registered', 'customer_registered:1');

        $this->manager->clear();

        $account = $this->accountFor($customer, $channel);
        self::assertNotNull($account);
        self::assertSame(250, $account->getBalance());
    }

    /**
     * @test
     */
    public function awarding_the_same_source_twice_is_idempotent(): void
    {
        $channel = $this->createChannel('action-idem');
        $customer = $this->createCustomer('action-idem@example.com');
        $this->createFixedPointsRule($channel, 'customer_registered', 250);

        $this->awarder->award($customer, $channel, 'customer_registered', 'customer_registered:2');
        $this->awarder->award($customer, $channel, 'customer_registered', 'customer_registered:2');

        $this->manager->clear();

        $account = $this->accountFor($customer, $channel);
        self::assertNotNull($account);
        self::assertSame(250, $account->getBalance());
        self::assertCount(1, $this->manager->getRepository(EarnActionLoyaltyTransaction::class)->findBy(['account' => $account]));
    }

    /**
     * @test
     */
    public function it_ignores_rules_registered_for_a_different_trigger(): void
    {
        $channel = $this->createChannel('action-other');
        $customer = $this->createCustomer('action-other@example.com');
        $this->createFixedPointsRule($channel, 'order_eligible', 250);

        $this->awarder->award($customer, $channel, 'customer_registered', 'customer_registered:3');

        $this->manager->clear();

        $account = $this->accountFor($customer, $channel);
        self::assertTrue(null === $account || 0 === $account->getBalance());
    }

    /**
     * @test
     */
    public function it_does_not_award_to_a_disabled_account(): void
    {
        $channel = $this->createChannel('action-disabled');
        $customer = $this->createCustomer('action-disabled@example.com');
        $this->createFixedPointsRule($channel, 'customer_registered', 250);

        $account = new LoyaltyAccount();
        $account->setCustomer($customer);
        $account->setChannel($channel);
        $account->setEnabled(false);
        $this->manager->persist($account);
        $this->manager->flush();

        $this->awarder->award($customer, $channel, 'customer_registered', 'customer_registered:4');

        $this->manager->clear();

        $account = $this->accountFor($customer, $channel);
        self::assertNotNull($account);
        self::assertSame(0, $account->getBalance());
    }

    /**
     * @test
     */
    public function it_skips_a_rule_whose_condition_does_not_match(): void
    {
        $channel = $this->createChannel('action-condition');
        $customer = $this->createCustomer('action-condition@example.com');

        $rule = new EarningRule();
        $rule->setChannel($channel);
        $rule->setEnabled(true);
        $rule->setTrigger('customer_registered');
        $rule->setAmountType('fixed');
        $rule->setAmountConfiguration(['points' => 250]);
        $condition = new EarningRuleCondition();
        $condition->setType('date_window');
        $condition->setConfiguration(['to' => '2000-01-01']);
        $rule->addCondition($condition);
        $this->manager->persist($rule);
        $this->manager->flush();

        $this->awarder->award($customer, $channel, 'customer_registered', 'customer_registered:6', new \DateTimeImmutable('2026-01-01'));

        $this->manager->clear();

        $account = $this->accountFor($customer, $channel);
        self::assertTrue(null === $account || 0 === $account->getBalance());
    }

    /**
     * @test
     */
    public function it_does_not_set_an_expiry_when_the_program_does_not_expire_points(): void
    {
        $channel = $this->createChannel('action-no-expiry');
        $customer = $this->createCustomer('action-no-expiry@example.com');
        $this->createFixedPointsRule($channel, 'customer_registered', 250);

        $program = new LoyaltyProgram();
        $program->setChannel($channel);
        $program->setPointsExpiryDays(null);
        $this->manager->persist($program);
        $this->manager->flush();

        $this->awarder->award($customer, $channel, 'customer_registered', 'customer_registered:7');

        $this->manager->clear();

        $account = $this->accountFor($customer, $channel);
        self::assertNotNull($account);
        $transaction = $this->manager->getRepository(EarnActionLoyaltyTransaction::class)->findOneBy(['account' => $account]);
        self::assertInstanceOf(EarnActionLoyaltyTransaction::class, $transaction);
        self::assertNull($transaction->getExpiresAt());
    }

    /**
     * @test
     */
    public function it_sets_an_expiry_when_the_program_expires_points(): void
    {
        $channel = $this->createChannel('action-expiry');
        $customer = $this->createCustomer('action-expiry@example.com');
        $this->createFixedPointsRule($channel, 'customer_registered', 250);

        $program = new LoyaltyProgram();
        $program->setChannel($channel);
        $program->setPointsExpiryDays(30);
        $this->manager->persist($program);
        $this->manager->flush();

        $this->awarder->award($customer, $channel, 'customer_registered', 'customer_registered:5');

        $this->manager->clear();

        $account = $this->accountFor($customer, $channel);
        self::assertNotNull($account);
        $transaction = $this->manager->getRepository(EarnActionLoyaltyTransaction::class)->findOneBy(['account' => $account]);
        self::assertInstanceOf(EarnActionLoyaltyTransaction::class, $transaction);
        self::assertNotNull($transaction->getExpiresAt());
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

    private function createCustomer(string $email): CustomerInterface
    {
        $customer = $this->factory('sylius.factory.customer')->createNew();
        \assert($customer instanceof CustomerInterface);
        $customer->setEmail($email);
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
