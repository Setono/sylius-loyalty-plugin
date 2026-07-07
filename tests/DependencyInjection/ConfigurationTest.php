<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Setono\SyliusLoyaltyPlugin\DependencyInjection\Configuration;
use Setono\SyliusLoyaltyPlugin\Doctrine\ORM\LoyaltyProgramRepository;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgram;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Resource\Factory\Factory;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    /**
     * @test
     */
    public function it_registers_the_loyalty_program_resource_with_defaults(): void
    {
        $this->assertProcessedConfigurationEquals([[]], [
            'resources' => [
                'program' => [
                    'classes' => [
                        'model' => LoyaltyProgram::class,
                        'interface' => LoyaltyProgramInterface::class,
                        'controller' => ResourceController::class,
                        'repository' => LoyaltyProgramRepository::class,
                        'factory' => Factory::class,
                    ],
                ],
            ],
        ]);
    }

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }
}
