<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProvider;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class LoyaltyAccountProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_returns_an_existing_account(): void
    {
        $customer = $this->prophesize(CustomerInterface::class)->reveal();
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $account = new LoyaltyAccount();

        $repository = $this->prophesize(LoyaltyAccountRepositoryInterface::class);
        $repository->findOneByCustomerAndChannel($customer, $channel)->willReturn($account);

        $factory = $this->prophesize(FactoryInterface::class);
        $factory->createNew()->shouldNotBeCalled();

        $managerRegistry = $this->prophesize(ManagerRegistry::class);

        $provider = new LoyaltyAccountProvider($repository->reveal(), $factory->reveal(), $managerRegistry->reveal());

        self::assertSame($account, $provider->getByCustomerAndChannel($customer, $channel));
    }

    /**
     * @test
     */
    public function it_creates_an_account_lazily(): void
    {
        $customer = $this->prophesize(CustomerInterface::class)->reveal();
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $account = new LoyaltyAccount();

        $repository = $this->prophesize(LoyaltyAccountRepositoryInterface::class);
        $repository->findOneByCustomerAndChannel($customer, $channel)->willReturn(null);

        $factory = $this->prophesize(FactoryInterface::class);
        $factory->createNew()->willReturn($account);

        $manager = $this->prophesize(ObjectManager::class);
        $manager->persist($account)->shouldBeCalled();
        $manager->flush()->shouldBeCalled();

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(LoyaltyAccount::class)->willReturn($manager->reveal());

        $provider = new LoyaltyAccountProvider($repository->reveal(), $factory->reveal(), $managerRegistry->reveal());

        $result = $provider->getByCustomerAndChannel($customer, $channel);

        self::assertSame($account, $result);
        self::assertSame($customer, $result->getCustomer());
        self::assertSame($channel, $result->getChannel());
    }
}
