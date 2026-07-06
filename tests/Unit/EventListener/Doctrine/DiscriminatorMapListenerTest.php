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
    public function it_adds_configured_types_to_the_discriminator_map(): void
    {
        $metadata = new ClassMetadata(LoyaltyTransaction::class);
        $metadata->setDiscriminatorMap(['earn_order' => EarnOrderLoyaltyTransaction::class]);

        $listener = new DiscriminatorMapListener(LoyaltyTransaction::class, [
            'earn_badge' => ManualCreditLoyaltyTransaction::class,
        ]);
        $listener->loadClassMetadata($this->eventArgs($metadata));

        self::assertSame([
            'earn_order' => EarnOrderLoyaltyTransaction::class,
            'earn_badge' => ManualCreditLoyaltyTransaction::class,
        ], $metadata->discriminatorMap);
    }

    /**
     * @test
     */
    public function it_ignores_other_classes(): void
    {
        $metadata = new ClassMetadata(LoyaltyAccount::class);

        $listener = new DiscriminatorMapListener(LoyaltyTransaction::class, [
            'earn_badge' => ManualCreditLoyaltyTransaction::class,
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
