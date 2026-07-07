<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Setono\SyliusLoyaltyPlugin\DependencyInjection\SetonoSyliusLoyaltyExtension;

final class SetonoSyliusLoyaltyExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new SetonoSyliusLoyaltyExtension(),
        ];
    }

    /**
     * @test
     */
    public function after_loading_the_correct_parameters_have_been_set(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter(
            'setono_sylius_loyalty.manual_adjustment_reasons',
            ['goodwill', 'correction', 'promotion', 'other'],
        );
        $this->assertContainerBuilderHasParameter('setono_sylius_loyalty.triggers', []);
        $this->assertContainerBuilderHasParameter(
            'setono_sylius_loyalty.expression_editor.cdn_base_url',
            'https://esm.sh',
        );
        $this->assertContainerBuilderHasParameter('setono_sylius_loyalty.driver', 'doctrine/orm');
    }
}
