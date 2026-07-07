<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LoyaltyAccountResourceTest extends KernelTestCase
{
    /**
     * @test
     */
    public function the_loyalty_account_entity_is_mapped_with_a_unique_customer_channel_pair(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        $metadata = $entityManager->getClassMetadata(LoyaltyAccount::class);

        self::assertSame('setono_sylius_loyalty__loyalty_account', $metadata->getTableName());
        self::assertTrue($metadata->hasAssociation('customer'));
        self::assertTrue($metadata->hasAssociation('channel'));

        $uniqueConstraints = $metadata->table['uniqueConstraints'] ?? [];
        self::assertIsArray($uniqueConstraints);

        $hasCustomerChannelUniqueConstraint = false;
        foreach ($uniqueConstraints as $constraint) {
            self::assertIsArray($constraint);
            if (['customer_id', 'channel_id'] === ($constraint['columns'] ?? null)) {
                $hasCustomerChannelUniqueConstraint = true;
            }
        }

        self::assertTrue($hasCustomerChannelUniqueConstraint, 'Expected a unique constraint on (customer_id, channel_id)');
    }
}
