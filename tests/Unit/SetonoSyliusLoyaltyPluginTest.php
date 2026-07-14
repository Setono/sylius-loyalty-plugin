<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Setono\CompositeCompilerPass\CompositeCompilerPass;
use Setono\SyliusLoyaltyPlugin\SetonoSyliusLoyaltyPlugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SetonoSyliusLoyaltyPluginTest extends TestCase
{
    /**
     * @test
     */
    public function it_registers_the_composite_trigger_channel_resolver_compiler_pass(): void
    {
        $container = new ContainerBuilder();

        (new SetonoSyliusLoyaltyPlugin())->build($container);

        $hasCompositePass = false;
        foreach ($container->getCompiler()->getPassConfig()->getPasses() as $pass) {
            if ($pass instanceof CompositeCompilerPass) {
                $hasCompositePass = true;

                break;
            }
        }

        self::assertTrue($hasCompositePass);
    }
}
