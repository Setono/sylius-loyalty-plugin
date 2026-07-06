<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Setono\SyliusLoyaltyPlugin\DependencyInjection\Compiler\RegisterEarningTriggersPass;
use Setono\SyliusLoyaltyPlugin\Event\Trigger\CustomerRegisteredTriggerEvent;
use Setono\SyliusLoyaltyPlugin\Event\Trigger\EarningTriggerEvent;
use Setono\SyliusLoyaltyPlugin\EventListener\EarningTriggerListener;
use Setono\SyliusLoyaltyPlugin\Exception\InvalidTriggerException;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class RegisterEarningTriggersPassTest extends TestCase
{
    /**
     * @test
     */
    public function it_registers_built_in_and_configured_triggers(): void
    {
        $container = self::container([NewsletterSubscribedTriggerEvent::class]);

        (new RegisterEarningTriggersPass())->process($container);

        /** @var array<string, array{class: string, label: string, context: array<string, string>}> $catalog */
        $catalog = $container->getParameter('setono_sylius_loyalty.trigger_catalog');

        self::assertArrayHasKey('customer_registered', $catalog);
        self::assertArrayHasKey('newsletter_subscribed', $catalog);
        self::assertSame(['source' => 'string'], $catalog['newsletter_subscribed']['context']);

        $tags = $container->getDefinition(EarningTriggerListener::class)->getTag('kernel.event_listener');
        $events = array_column($tags, 'event');
        self::assertContains(CustomerRegisteredTriggerEvent::class, $events);
        self::assertContains(NewsletterSubscribedTriggerEvent::class, $events);
    }

    /**
     * @test
     */
    public function it_rejects_classes_not_extending_the_trigger_base(): void
    {
        $container = self::container([\stdClass::class]);

        $this->expectException(InvalidTriggerException::class);

        (new RegisterEarningTriggersPass())->process($container);
    }

    /**
     * @test
     */
    public function it_rejects_colliding_trigger_codes(): void
    {
        $container = self::container([CollidingTriggerEvent::class]);

        $this->expectException(InvalidTriggerException::class);
        $this->expectExceptionMessageMatches('/collides/');

        (new RegisterEarningTriggersPass())->process($container);
    }

    /**
     * @param list<class-string> $triggers
     */
    private static function container(array $triggers): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('setono_sylius_loyalty.triggers', $triggers);
        $container->setDefinition(EarningTriggerListener::class, new Definition(EarningTriggerListener::class));

        return $container;
    }
}

final class NewsletterSubscribedTriggerEvent extends EarningTriggerEvent
{
    public function __construct(
        CustomerInterface $customer,
        public readonly string $source,
        ?ChannelInterface $channel = null,
    ) {
        parent::__construct($customer, $channel);
    }

    public static function getTriggerCode(): string
    {
        return 'newsletter_subscribed';
    }

    public static function getLabel(): string
    {
        return 'app.trigger.newsletter_subscribed';
    }
}

final class CollidingTriggerEvent extends EarningTriggerEvent
{
    public static function getTriggerCode(): string
    {
        return 'customer_registered';
    }

    public static function getLabel(): string
    {
        return 'app.trigger.colliding';
    }
}
