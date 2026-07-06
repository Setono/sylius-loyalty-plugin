<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyProgramRepositoryInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class LoyaltyProgramProvider implements LoyaltyProgramProviderInterface
{
    /**
     * @param FactoryInterface<LoyaltyProgramInterface> $programFactory
     */
    public function __construct(
        private readonly LoyaltyProgramRepositoryInterface $programRepository,
        private readonly FactoryInterface $programFactory,
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    public function getByChannel(ChannelInterface $channel): LoyaltyProgramInterface
    {
        $program = $this->programRepository->findOneByChannel($channel);
        if (null !== $program) {
            return $program;
        }

        $program = $this->programFactory->createNew();
        Assert::isInstanceOf($program, LoyaltyProgramInterface::class);
        $program->setChannel($channel);

        $manager = $this->managerRegistry->getManagerForClass($program::class);
        Assert::notNull($manager);

        try {
            $manager->persist($program);
            $manager->flush();
        } catch (UniqueConstraintViolationException) {
            // Another process created the program concurrently. The entity manager is closed
            // by the failed flush, so reset it and load the winning row.
            $this->managerRegistry->resetManager();

            $program = $this->programRepository->findOneByChannel($channel);
            Assert::notNull($program);
        }

        return $program;
    }
}
