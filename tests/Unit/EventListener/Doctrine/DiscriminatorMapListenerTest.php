<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\EventListener\Doctrine\DiscriminatorMapListener;
use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\ManualCreditLoyaltyTransaction;

final class DiscriminatorMapListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_adds_resource_registered_transaction_subclasses_to_the_discriminator_map(): void
    {
        $metadata = new ClassMetadata(LoyaltyTransaction::class);
        $metadata->setDiscriminatorMap(['earn_order' => EarnOrderLoyaltyTransaction::class]);

        $listener = new DiscriminatorMapListener(LoyaltyTransaction::class, [
            'app.badge_transaction' => ['classes' => ['model' => ManualCreditLoyaltyTransaction::class]],
            'sylius.product' => ['classes' => ['model' => LoyaltyAccount::class]],
        ]);
        $listener->loadClassMetadata($this->eventArgs($metadata));

        self::assertSame([
            'earn_order' => EarnOrderLoyaltyTransaction::class,
            'manual_credit' => ManualCreditLoyaltyTransaction::class,
        ], $metadata->discriminatorMap);
    }

    /**
     * @test
     */
    public function it_does_not_re_add_classes_already_in_the_map(): void
    {
        $metadata = new ClassMetadata(LoyaltyTransaction::class);
        $metadata->setDiscriminatorMap(['earn_order' => EarnOrderLoyaltyTransaction::class]);

        $listener = new DiscriminatorMapListener(LoyaltyTransaction::class, [
            'setono_sylius_loyalty.transaction' => ['classes' => ['model' => EarnOrderLoyaltyTransaction::class]],
        ]);
        $listener->loadClassMetadata($this->eventArgs($metadata));

        self::assertSame(['earn_order' => EarnOrderLoyaltyTransaction::class], $metadata->discriminatorMap);
    }

    /**
     * @test
     */
    public function it_ignores_other_classes(): void
    {
        $metadata = new ClassMetadata(LoyaltyAccount::class);

        $listener = new DiscriminatorMapListener(LoyaltyTransaction::class, [
            'app.badge_transaction' => ['classes' => ['model' => ManualCreditLoyaltyTransaction::class]],
        ]);
        $listener->loadClassMetadata($this->eventArgs($metadata));

        self::assertSame([], $metadata->discriminatorMap);
    }

    /**
     * @param ClassMetadata<object> $metadata
     */
    private function eventArgs(ClassMetadata $metadata): LoadClassMetadataEventArgs
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);

        return new LoadClassMetadataEventArgs($metadata, $entityManager->reveal());
    }
}
