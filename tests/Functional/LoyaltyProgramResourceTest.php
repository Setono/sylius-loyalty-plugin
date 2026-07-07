<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgram;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LoyaltyProgramResourceTest extends KernelTestCase
{
    /**
     * @test
     */
    public function the_loyalty_program_entity_is_mapped_to_the_prefixed_table(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        $metadata = $entityManager->getClassMetadata(LoyaltyProgram::class);

        self::assertSame('setono_sylius_loyalty__loyalty_program', $metadata->getTableName());
        self::assertTrue($metadata->hasAssociation('channel'));
    }
}
