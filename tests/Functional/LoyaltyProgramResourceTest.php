<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgram;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProvider;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LoyaltyProgramResourceTest extends KernelTestCase
{
    /**
     * @test
     */
    public function the_loyalty_program_provider_is_wired(): void
    {
        self::bootKernel();

        $provider = self::getContainer()->get(LoyaltyProgramProviderInterface::class);

        self::assertInstanceOf(LoyaltyProgramProvider::class, $provider);
    }

    /**
     * @test
     */
    public function the_loyalty_program_entity_is_mapped_to_the_prefixed_table(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        $metadata = $entityManager->getClassMetadata(LoyaltyProgram::class);

        self::assertSame('setono_sylius_loyalty__program', $metadata->getTableName());
        self::assertTrue($metadata->hasAssociation('channel'));
    }
}
