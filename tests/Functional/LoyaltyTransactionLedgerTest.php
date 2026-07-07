<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransaction;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LoyaltyTransactionLedgerTest extends KernelTestCase
{
    /**
     * @test
     */
    public function the_ledger_is_a_single_table_inheritance_root(): void
    {
        self::bootKernel();

        $metadata = $this->entityManager()->getClassMetadata(LoyaltyTransaction::class);

        self::assertSame('setono_sylius_loyalty__loyalty_transaction', $metadata->getTableName());
        self::assertSame(ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE, $metadata->inheritanceType);
        self::assertSame('type', $metadata->discriminatorColumn['name'] ?? null);
    }

    /**
     * @test
     */
    public function the_discriminator_map_is_built_from_the_registered_transaction_resources(): void
    {
        self::bootKernel();

        $metadata = $this->entityManager()->getClassMetadata(LoyaltyTransaction::class);

        self::assertEqualsCanonicalizing([
            'earn_order',
            'earn_action',
            'redeem_rollback',
            'manual_credit',
            'redeem',
            'manual_debit',
            'expire',
            'clawback',
        ], array_keys($metadata->discriminatorMap), 'The listener should register every transaction resource in the discriminator map');
    }

    /**
     * @test
     */
    public function a_concrete_type_shares_the_root_table_with_its_own_discriminator(): void
    {
        self::bootKernel();

        $metadata = $this->entityManager()->getClassMetadata(EarnOrderLoyaltyTransaction::class);

        self::assertSame('setono_sylius_loyalty__loyalty_transaction', $metadata->getTableName());
        self::assertSame('earn_order', $metadata->discriminatorValue);
    }

    private function entityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($entityManager instanceof EntityManagerInterface);

        return $entityManager;
    }
}
