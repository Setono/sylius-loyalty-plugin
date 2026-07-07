<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyProgramRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class LoyaltyProgramProvider implements LoyaltyProgramProviderInterface
{
    use ORMTrait;

    /**
     * @param FactoryInterface<LoyaltyProgramInterface> $factory
     */
    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly LoyaltyProgramRepositoryInterface $repository,
        ManagerRegistry $managerRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function getProgram(ChannelInterface $channel): LoyaltyProgramInterface
    {
        $program = $this->repository->findOneByChannel($channel);
        if (null !== $program) {
            return $program;
        }

        $program = $this->factory->createNew();
        $program->setChannel($channel);

        $manager = $this->getManager($program);
        $manager->persist($program);
        $manager->flush();

        return $program;
    }
}
