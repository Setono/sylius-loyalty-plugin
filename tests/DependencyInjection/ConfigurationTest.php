<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Setono\SyliusLoyaltyPlugin\DependencyInjection\Configuration;
use Setono\SyliusLoyaltyPlugin\Doctrine\ORM\LoyaltyAccountRepository;
use Setono\SyliusLoyaltyPlugin\Doctrine\ORM\LoyaltyProgramRepository;
use Setono\SyliusLoyaltyPlugin\Doctrine\ORM\LoyaltyTransactionRepository;
use Setono\SyliusLoyaltyPlugin\Model\DryRunResult;
use Setono\SyliusLoyaltyPlugin\Model\DryRunResultInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarningRule;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleCondition;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleConditionInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgram;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;
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
                'account' => self::resource(LoyaltyAccount::class, LoyaltyAccountInterface::class, LoyaltyAccountRepository::class),
                'program' => self::resource(LoyaltyProgram::class, LoyaltyProgramInterface::class, LoyaltyProgramRepository::class),
                'transaction' => self::resource(LoyaltyTransaction::class, LoyaltyTransactionInterface::class, LoyaltyTransactionRepository::class),
                'earning_rule' => self::resource(EarningRule::class, EarningRuleInterface::class),
                'earning_rule_condition' => self::resource(EarningRuleCondition::class, EarningRuleConditionInterface::class),
                'dry_run_result' => self::resource(DryRunResult::class, DryRunResultInterface::class),
            ],
        ]);
    }

    /**
     * @param class-string $model
     * @param class-string $interface
     * @param class-string|null $repository
     *
     * @return array{classes: array<string, class-string>}
     */
    private static function resource(string $model, string $interface, ?string $repository = null): array
    {
        $classes = [
            'model' => $model,
            'interface' => $interface,
            'controller' => ResourceController::class,
            'factory' => Factory::class,
        ];

        if (null !== $repository) {
            $classes['repository'] = $repository;
        }

        return ['classes' => $classes];
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
