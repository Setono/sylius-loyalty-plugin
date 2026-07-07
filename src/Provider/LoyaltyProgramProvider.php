<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider;

use Doctrine\Persistence\ObjectManager;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyProgramRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class LoyaltyProgramProvider implements LoyaltyProgramProviderInterface
{
    /**
     * @param FactoryInterface<LoyaltyProgramInterface> $factory
     */
    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly LoyaltyProgramRepositoryInterface $repository,
        private readonly ObjectManager $manager,
    ) {
    }

    public function getProgram(ChannelInterface $channel): LoyaltyProgramInterface
    {
        $program = $this->repository->findOneByChannel($channel);
        if (null !== $program) {
            return $program;
        }

        $program = $this->factory->createNew();
        $program->setChannel($channel);

        $this->manager->persist($program);
        $this->manager->flush();

        return $program;
    }
}
