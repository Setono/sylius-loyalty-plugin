<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Setono\SyliusLoyaltyPlugin\DependencyInjection\Configuration;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    /**
     * @test
     */
    public function it_processes_an_empty_configuration_to_empty_resources(): void
    {
        $this->assertProcessedConfigurationEquals([[]], ['resources' => []]);
    }

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }
}
