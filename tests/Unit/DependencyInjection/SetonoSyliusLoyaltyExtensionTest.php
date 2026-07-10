<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\DependencyInjection\SetonoSyliusLoyaltyExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

final class SetonoSyliusLoyaltyExtensionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_does_not_prepend_winzou_configuration_when_winzou_is_not_installed(): void
    {
        $container = new ContainerBuilder();

        (new SetonoSyliusLoyaltyExtension())->prepend($container);

        self::assertSame([], $container->getExtensionConfig('winzou_state_machine'));
    }

    /**
     * @test
     */
    public function it_prepends_a_dedicated_command_bus(): void
    {
        $container = new ContainerBuilder();

        (new SetonoSyliusLoyaltyExtension())->prepend($container);

        $encoded = json_encode($container->getExtensionConfig('framework'));
        self::assertIsString($encoded);
        self::assertStringContainsString('setono_sylius_loyalty.command_bus', $encoded);
    }

    /**
     * @test
     */
    public function it_prepends_winzou_award_and_clawback_callbacks_for_the_relevant_transitions(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension($this->winzouExtension());

        (new SetonoSyliusLoyaltyExtension())->prepend($container);

        $config = $container->getExtensionConfig('winzou_state_machine');
        self::assertCount(1, $config);

        $encoded = json_encode($config[0]);
        self::assertIsString($encoded);
        self::assertStringContainsString('sylius_order_payment', $encoded);
        self::assertStringContainsString('sylius_order', $encoded);
        self::assertStringContainsString('sylius_order_checkout', $encoded);
        self::assertStringContainsString('setono_sylius_loyalty_award_order_points', $encoded);
        self::assertStringContainsString('setono_sylius_loyalty_clawback_order_points', $encoded);
        self::assertStringContainsString('setono_sylius_loyalty_redeem_order_points', $encoded);
        self::assertStringContainsString('setono_sylius_loyalty_rollback_redemption', $encoded);
        self::assertStringContainsString('sylius_product_review', $encoded);
        self::assertStringContainsString('setono_sylius_loyalty_award_review_points', $encoded);
        self::assertStringContainsString('"on":["pay"]', $encoded);
        self::assertStringContainsString('"on":["fulfill"]', $encoded);
        self::assertStringContainsString('"on":["refund"]', $encoded);
        self::assertStringContainsString('"on":["cancel"]', $encoded);
        self::assertStringContainsString('"on":["complete"]', $encoded);
        self::assertStringContainsString('"on":["accept"]', $encoded);
    }

    /**
     * @test
     */
    public function it_prepends_the_redemption_widget_into_the_cart_summary(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension($this->extension('sylius_ui'));

        (new SetonoSyliusLoyaltyExtension())->prepend($container);

        $config = $container->getExtensionConfig('sylius_ui');
        self::assertCount(1, $config);

        $encoded = json_encode($config[0]);
        self::assertIsString($encoded);
        self::assertStringContainsString('sylius.shop.cart.summary.items', $encoded);
        self::assertStringContainsString('setono_sylius_loyalty_redemption', $encoded);
        self::assertStringContainsString('_redemption.html.twig', $encoded);
    }

    /**
     * @test
     */
    public function it_does_not_prepend_the_widget_when_sylius_ui_is_not_installed(): void
    {
        $container = new ContainerBuilder();

        (new SetonoSyliusLoyaltyExtension())->prepend($container);

        self::assertSame([], $container->getExtensionConfig('sylius_ui'));
    }

    private function winzouExtension(): ExtensionInterface
    {
        return $this->extension('winzou_state_machine');
    }

    private function extension(string $alias): ExtensionInterface
    {
        $extension = $this->prophesize(ExtensionInterface::class);
        $extension->getAlias()->willReturn($alias);
        $extension->getNamespace()->willReturn('');

        return $extension->reveal();
    }
}
