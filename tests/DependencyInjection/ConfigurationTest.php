<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Setono\SyliusLoyaltyPlugin\DependencyInjection\Configuration;
use Setono\SyliusLoyaltyPlugin\Form\Type\EarningRuleConditionType;
use Setono\SyliusLoyaltyPlugin\Form\Type\EarningRuleType;
use Setono\SyliusLoyaltyPlugin\Form\Type\LoyaltyProgramType;
use Setono\SyliusLoyaltyPlugin\Form\Type\TierType;
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
use Setono\SyliusLoyaltyPlugin\Model\Referral;
use Setono\SyliusLoyaltyPlugin\Model\ReferralInterface;
use Setono\SyliusLoyaltyPlugin\Model\Tier;
use Setono\SyliusLoyaltyPlugin\Model\TierInterface;
use Setono\SyliusLoyaltyPlugin\Model\TierTranslation;
use Setono\SyliusLoyaltyPlugin\Model\TierTranslationInterface;
use Setono\SyliusLoyaltyPlugin\Repository\EarningRuleRepository;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepository;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyProgramRepository;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepository;
use Setono\SyliusLoyaltyPlugin\Repository\ReferralRepository;
use Setono\SyliusLoyaltyPlugin\Repository\TierRepository;
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
            'expression_editor' => [
                'cdn_base_url' => 'https://esm.sh',
            ],
            'referral' => [
                'query_parameter' => 'ref',
                'registration_ip_check' => false,
                'ip_hash_salt' => '%kernel.secret%',
                'reward_cap' => 10,
            ],
            'retain_anonymized_ledger' => false,
            'resources' => [
                'account' => self::resource(LoyaltyAccount::class, LoyaltyAccountInterface::class, LoyaltyAccountRepository::class),
                'program' => self::resource(LoyaltyProgram::class, LoyaltyProgramInterface::class, LoyaltyProgramRepository::class, LoyaltyProgramType::class),
                'transaction' => self::resource(LoyaltyTransaction::class, LoyaltyTransactionInterface::class, LoyaltyTransactionRepository::class),
                'earning_rule' => self::resource(EarningRule::class, EarningRuleInterface::class, EarningRuleRepository::class, EarningRuleType::class),
                'earning_rule_condition' => self::resource(EarningRuleCondition::class, EarningRuleConditionInterface::class, null, EarningRuleConditionType::class),
                'dry_run_result' => self::resource(DryRunResult::class, DryRunResultInterface::class),
                'referral' => self::resource(Referral::class, ReferralInterface::class, ReferralRepository::class),
                'tier' => self::resource(Tier::class, TierInterface::class, TierRepository::class, TierType::class) + [
                    'translation' => [
                        'classes' => [
                            'model' => TierTranslation::class,
                            'interface' => TierTranslationInterface::class,
                            'controller' => ResourceController::class,
                            'factory' => Factory::class,
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param class-string $model
     * @param class-string $interface
     * @param class-string|null $repository
     * @param class-string|null $form
     *
     * @return array{classes: array<string, class-string>}
     */
    private static function resource(string $model, string $interface, ?string $repository = null, ?string $form = null): array
    {
        $classes = [
            'model' => $model,
            'interface' => $interface,
            'controller' => ResourceController::class,
            'factory' => Factory::class,
        ];

        if (null !== $form) {
            $classes['form'] = $form;
        }

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
}
