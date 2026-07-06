<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Setono\SyliusLoyaltyPlugin\DependencyInjection\Configuration;

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
            'resources' => [],
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
