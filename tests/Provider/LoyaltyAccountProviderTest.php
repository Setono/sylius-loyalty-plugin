<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProvider;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class LoyaltyAccountProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_returns_the_existing_account_for_a_customer_and_channel(): void
    {
        $customer = $this->prophesize(CustomerInterface::class)->reveal();
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $account = $this->prophesize(LoyaltyAccountInterface::class)->reveal();

        $repository = $this->prophesize(LoyaltyAccountRepositoryInterface::class);
        $repository->findOneByCustomerAndChannel($customer, $channel)->willReturn($account);

        $factory = $this->prophesize(FactoryInterface::class);
        $managerRegistry = $this->prophesize(ManagerRegistry::class);

        $provider = new LoyaltyAccountProvider($factory->reveal(), $repository->reveal(), $managerRegistry->reveal());

        self::assertSame($account, $provider->getAccount($customer, $channel));

        $factory->createNew()->shouldNotHaveBeenCalled();
        $managerRegistry->getManagerForClass(Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function it_creates_an_account_on_first_access(): void
    {
        $customer = $this->prophesize(CustomerInterface::class)->reveal();
        $channel = $this->prophesize(ChannelInterface::class)->reveal();

        $repository = $this->prophesize(LoyaltyAccountRepositoryInterface::class);
        $repository->findOneByCustomerAndChannel($customer, $channel)->willReturn(null);

        $account = $this->prophesize(LoyaltyAccountInterface::class);
        $account->setCustomer($customer)->shouldBeCalled();
        $account->setChannel($channel)->shouldBeCalled();

        $factory = $this->prophesize(FactoryInterface::class);
        $factory->createNew()->willReturn($account->reveal());

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Argument::any())->willReturn($entityManager->reveal());

        $provider = new LoyaltyAccountProvider($factory->reveal(), $repository->reveal(), $managerRegistry->reveal());

        self::assertSame($account->reveal(), $provider->getAccount($customer, $channel));

        $entityManager->persist($account->reveal())->shouldHaveBeenCalled();
        $entityManager->flush()->shouldHaveBeenCalled();
    }
}
