<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Setono\SyliusLoyaltyPlugin\DependencyInjection\SetonoSyliusLoyaltyExtension;

final class SetonoSyliusLoyaltyExtensionTest extends AbstractExtensionTestCase
{
    /**
     * @test
     */
    public function it_registers_the_doctrine_orm_driver(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('setono_sylius_loyalty.driver', 'doctrine/orm');
        $this->assertContainerBuilderHasParameter('setono_sylius_loyalty.driver.doctrine/orm', true);
    }

    /**
     * @return list<SetonoSyliusLoyaltyExtension>
     */
    protected function getContainerExtensions(): array
    {
        return [
            new SetonoSyliusLoyaltyExtension(),
        ];
    }
}
