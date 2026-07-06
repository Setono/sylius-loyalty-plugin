<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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
        $rootNode
            ->children()
                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
                        // Resource nodes are added here as the corresponding models are implemented
                    ->end()
                ->end()
            ->end()
        ;
    }
}
