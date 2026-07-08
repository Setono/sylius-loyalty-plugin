<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\DependencyInjection;

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
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('setono_sylius_loyalty');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $this->addResourcesSection($rootNode);

        return $treeBuilder;
    }

    private function addResourcesSection(ArrayNodeDefinition $node): void
    {
        /** @var NodeBuilder $resources */
        $resources = $node
            ->children()
                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
        ;

        $this->addResource($resources, 'program', LoyaltyProgram::class, LoyaltyProgramRepository::class);
        // The account interface is registered so other entities (transactions, tiers, referrals)
        // can reference LoyaltyAccountInterface in their mappings via a resolve_target_entity.
        $this->addResource($resources, 'account', LoyaltyAccount::class, LoyaltyAccountRepository::class, LoyaltyAccountInterface::class);

        // The earning rule aggregate. Both are registered with their interface so the rule <-> condition
        // association resolves via resolve_target_entity. A custom rule repository lands with the
        // evaluation pipeline that needs its queries.
        $this->addResource($resources, 'earning_rule', EarningRule::class, EntityRepository::class, EarningRuleInterface::class);
        $this->addResource($resources, 'earning_rule_condition', EarningRuleCondition::class, EntityRepository::class, EarningRuleConditionInterface::class);

        // The concrete ledger transaction types. Registering them as resources feeds the
        // discriminator-map listener (%sylius.resources%), so a project adds a transaction type by
        // registering it here too. Their mappings are mapped-superclasses (like every Sylius model) so
        // applications can override them; the resource bundle promotes them to the STI entities at
        // runtime. Only the abstract root and intermediates stay entities — the root is the
        // single-table entity, and the intermediates are foreign-key targets that need a table.
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
            $this->addTransactionResource($resources, $name, $model);
        }
    }

    /**
     * @param class-string $model
     */
    private function addTransactionResource(NodeBuilder $resources, string $name, string $model): void
    {
        $resources
            ->arrayNode($name)
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('classes')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('model')->defaultValue($model)->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * @param class-string $model
     * @param class-string $repository
     * @param class-string|null $interface
     */
    private function addResource(NodeBuilder $resources, string $name, string $model, string $repository, ?string $interface = null): void
    {
        $classes = $resources
            ->arrayNode($name)
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('classes')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('model')->defaultValue($model)->cannotBeEmpty()->end()
                            ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                            ->scalarNode('repository')->defaultValue($repository)->cannotBeEmpty()->end()
                            ->scalarNode('factory')->defaultValue(Factory::class)->cannotBeEmpty()->end()
        ;

        if (null !== $interface) {
            $classes->scalarNode('interface')->defaultValue($interface)->cannotBeEmpty()->end();
        }

        $classes
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
