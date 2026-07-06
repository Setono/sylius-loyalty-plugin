<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\DependencyInjection;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgram;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Resource\Factory\Factory;
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

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('manual_adjustment_reasons')
                    ->info('Reason codes selectable when an admin manually adjusts a loyalty account. Labels resolve via the translation key "setono_sylius_loyalty.ui.manual_reason.<code>"')
                    ->defaultValue(['goodwill', 'correction', 'promotion', 'other'])
                    ->scalarPrototype()->cannotBeEmpty()->end()
                ->end()
                ->arrayNode('triggers')
                    ->info('Earning trigger event classes. Each class must extend Setono\SyliusLoyaltyPlugin\Event\Trigger\EarningTriggerEvent')
                    ->defaultValue([])
                    ->scalarPrototype()->cannotBeEmpty()->end()
                ->end()
                ->arrayNode('transaction_types')
                    ->info('Custom loyalty transaction types added to the Doctrine discriminator map: discriminator value => transaction class')
                    ->useAttributeAsKey('type')
                    ->defaultValue([])
                    ->scalarPrototype()->cannotBeEmpty()->end()
                ->end()
                ->arrayNode('expression_editor')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('cdn_base_url')
                            ->info('Base URL for the version-pinned ESM imports (CodeMirror) used by the admin expression editor. Point it at a self-hosted copy for intranet or strict-CSP setups')
                            ->defaultValue('https://esm.sh')
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        $this->addResourcesSection($rootNode);

        return $treeBuilder;
    }

    private function addResourcesSection(ArrayNodeDefinition $rootNode): void
    {
        /** @var NodeBuilder $resources */
        $resources = $rootNode
            ->children()
                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
        ;

        $this->addResourceNode($resources, 'account', LoyaltyAccount::class, LoyaltyAccountInterface::class);
        $this->addResourceNode($resources, 'program', LoyaltyProgram::class, LoyaltyProgramInterface::class);
    }

    /**
     * @param class-string $model
     * @param class-string $interface
     */
    private function addResourceNode(NodeBuilder $resources, string $name, string $model, string $interface): void
    {
        $resources
            ->arrayNode($name)
                ->addDefaultsIfNotSet()
                ->children()
                    ->variableNode('options')->end()
                    ->arrayNode('classes')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('model')->defaultValue($model)->cannotBeEmpty()->end()
                            ->scalarNode('interface')->defaultValue($interface)->cannotBeEmpty()->end()
                            ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
                            ->scalarNode('repository')->cannotBeEmpty()->end()
                            ->scalarNode('factory')->defaultValue(Factory::class)->cannotBeEmpty()->end()
                            ->scalarNode('form')->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
