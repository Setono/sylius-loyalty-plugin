<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProvider;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyProgramRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class LoyaltyProgramProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_returns_the_existing_program_for_a_channel(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $program = $this->prophesize(LoyaltyProgramInterface::class)->reveal();

        $repository = $this->prophesize(LoyaltyProgramRepositoryInterface::class);
        $repository->findOneByChannel($channel)->willReturn($program);

        $factory = $this->prophesize(FactoryInterface::class);
        $managerRegistry = $this->prophesize(ManagerRegistry::class);

        $provider = new LoyaltyProgramProvider($factory->reveal(), $repository->reveal(), $managerRegistry->reveal());

        self::assertSame($program, $provider->getProgram($channel));

        $factory->createNew()->shouldNotHaveBeenCalled();
        $managerRegistry->getManagerForClass(Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function it_creates_a_program_with_defaults_on_first_access(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();

        $repository = $this->prophesize(LoyaltyProgramRepositoryInterface::class);
        $repository->findOneByChannel($channel)->willReturn(null);

        $program = $this->prophesize(LoyaltyProgramInterface::class);
        $program->setChannel($channel)->shouldBeCalled();

        $factory = $this->prophesize(FactoryInterface::class);
        $factory->createNew()->willReturn($program->reveal());

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Argument::any())->willReturn($entityManager->reveal());

        $provider = new LoyaltyProgramProvider($factory->reveal(), $repository->reveal(), $managerRegistry->reveal());

        self::assertSame($program->reveal(), $provider->getProgram($channel));

        $entityManager->persist($program->reveal())->shouldHaveBeenCalled();
        $entityManager->flush()->shouldHaveBeenCalled();
    }
}
