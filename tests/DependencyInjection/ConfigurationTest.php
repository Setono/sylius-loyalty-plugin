<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Setono\SyliusLoyaltyPlugin\DependencyInjection\Configuration;
use Setono\SyliusLoyaltyPlugin\Doctrine\ORM\LoyaltyAccountRepository;
use Setono\SyliusLoyaltyPlugin\Doctrine\ORM\LoyaltyProgramRepository;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgram;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Resource\Factory\Factory;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    /**
     * @test
     */
    public function it_registers_the_resources_with_defaults(): void
    {
        $this->assertProcessedConfigurationEquals([[]], [
            'resources' => [
                'program' => [
                    'classes' => [
                        'model' => LoyaltyProgram::class,
                        'controller' => ResourceController::class,
                        'repository' => LoyaltyProgramRepository::class,
                        'factory' => Factory::class,
                    ],
                ],
                'account' => [
                    'classes' => [
                        'model' => LoyaltyAccount::class,
                        'controller' => ResourceController::class,
                        'repository' => LoyaltyAccountRepository::class,
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
