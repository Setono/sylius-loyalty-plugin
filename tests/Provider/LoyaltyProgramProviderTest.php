<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Provider;

use Doctrine\Persistence\ObjectManager;
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
        $manager = $this->prophesize(ObjectManager::class);

        $provider = new LoyaltyProgramProvider($factory->reveal(), $repository->reveal(), $manager->reveal());

        self::assertSame($program, $provider->getProgram($channel));

        $factory->createNew()->shouldNotHaveBeenCalled();
        $manager->persist(Argument::any())->shouldNotHaveBeenCalled();
        $manager->flush()->shouldNotHaveBeenCalled();
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

        $manager = $this->prophesize(ObjectManager::class);

        $provider = new LoyaltyProgramProvider($factory->reveal(), $repository->reveal(), $manager->reveal());

        self::assertSame($program->reveal(), $provider->getProgram($channel));

        $manager->persist($program->reveal())->shouldHaveBeenCalled();
        $manager->flush()->shouldHaveBeenCalled();
    }
}
