<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Setono\SyliusLoyaltyPlugin\DependencyInjection\Configuration;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgram;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Resource\Factory\Factory;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }

    /**
     * @test
     */
    public function it_has_sensible_defaults(): void
    {
        $this->assertProcessedConfigurationEquals([
            [],
        ], [
            'manual_adjustment_reasons' => ['goodwill', 'correction', 'promotion', 'other'],
            'triggers' => [],
            'transaction_types' => [],
            'expression_editor' => [
                'cdn_base_url' => 'https://esm.sh',
            ],
            'resources' => [
                'account' => [
                    'classes' => [
                        'model' => LoyaltyAccount::class,
                        'interface' => LoyaltyAccountInterface::class,
                        'controller' => ResourceController::class,
                        'factory' => Factory::class,
                    ],
                ],
                'program' => [
                    'classes' => [
                        'model' => LoyaltyProgram::class,
                        'interface' => LoyaltyProgramInterface::class,
                        'controller' => ResourceController::class,
                        'factory' => Factory::class,
                    ],
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function it_allows_overriding_manual_adjustment_reasons(): void
    {
        $this->assertProcessedConfigurationEquals([
            ['manual_adjustment_reasons' => ['goodwill', 'vip']],
        ], [
            'manual_adjustment_reasons' => ['goodwill', 'vip'],
        ], 'manual_adjustment_reasons');
    }

    /**
     * @test
     */
    public function it_allows_registering_triggers(): void
    {
        $this->assertProcessedConfigurationEquals([
            ['triggers' => ['App\Event\NewsletterSubscribedTriggerEvent']],
        ], [
            'triggers' => ['App\Event\NewsletterSubscribedTriggerEvent'],
        ], 'triggers');
    }

    /**
     * @test
     */
    public function it_allows_registering_custom_transaction_types(): void
    {
        $this->assertProcessedConfigurationEquals([
            ['transaction_types' => ['earn_badge' => 'App\Model\EarnBadgeLoyaltyTransaction']],
        ], [
            'transaction_types' => ['earn_badge' => 'App\Model\EarnBadgeLoyaltyTransaction'],
        ], 'transaction_types');
    }
}
