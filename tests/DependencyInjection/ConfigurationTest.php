<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Setono\SyliusLoyaltyPlugin\DependencyInjection\Configuration;
use Setono\SyliusLoyaltyPlugin\Doctrine\ORM\EarningRuleRepository;
use Setono\SyliusLoyaltyPlugin\Doctrine\ORM\LoyaltyAccountRepository;
use Setono\SyliusLoyaltyPlugin\Doctrine\ORM\LoyaltyProgramRepository;
use Setono\SyliusLoyaltyPlugin\Model\ClawbackLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\EarningRule;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleCondition;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleConditionInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\ExpireLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgram;
use Setono\SyliusLoyaltyPlugin\Model\ManualCreditLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\ManualDebitLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\RedeemRollbackLoyaltyTransaction;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Resource\Factory\Factory;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    /**
     * @test
     */
    public function it_registers_the_resources_with_defaults(): void
    {
        $transactions = [];
        foreach ([
            'earn_order' => EarnOrderLoyaltyTransaction::class,
            'earn_action' => EarnActionLoyaltyTransaction::class,
            'redeem_rollback' => RedeemRollbackLoyaltyTransaction::class,
            'manual_credit' => ManualCreditLoyaltyTransaction::class,
            'redeem' => RedeemLoyaltyTransaction::class,
            'manual_debit' => ManualDebitLoyaltyTransaction::class,
            'expire' => ExpireLoyaltyTransaction::class,
            'clawback' => ClawbackLoyaltyTransaction::class,
        ] as $name => $model) {
            $transactions[$name] = ['classes' => ['model' => $model]];
        }

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
                        'interface' => LoyaltyAccountInterface::class,
                    ],
                ],
                'earning_rule' => [
                    'classes' => [
                        'model' => EarningRule::class,
                        'controller' => ResourceController::class,
                        'repository' => EarningRuleRepository::class,
                        'factory' => Factory::class,
                        'interface' => EarningRuleInterface::class,
                    ],
                ],
                'earning_rule_condition' => [
                    'classes' => [
                        'model' => EarningRuleCondition::class,
                        'controller' => ResourceController::class,
                        'repository' => EntityRepository::class,
                        'factory' => Factory::class,
                        'interface' => EarningRuleConditionInterface::class,
                    ],
                ],
                ...$transactions,
            ],
        ]);
    }

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }
}
