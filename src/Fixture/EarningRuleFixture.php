<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\AbstractResourceFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class EarningRuleFixture extends AbstractResourceFixture
{
    public function getName(): string
    {
        return 'setono_sylius_loyalty_earning_rule';
    }

    protected function configureResourceNode(ArrayNodeDefinition $resourceNode): void
    {
        $resourceNode
            ->children()
                ->scalarNode('name')->cannotBeEmpty()->end()
                ->scalarNode('channel')->cannotBeEmpty()->end()
                ->scalarNode('trigger')->end()
                ->scalarNode('scope')->end()
                ->variableNode('scope_configuration')->end()
                ->scalarNode('conditions_match')->end()
                ->variableNode('conditions')->end()
                ->scalarNode('amount_type')->cannotBeEmpty()->end()
                ->variableNode('amount_configuration')->end()
                ->integerNode('priority')->end()
                ->booleanNode('stackable')->end()
                ->booleanNode('enabled')->end()
                ->booleanNode('dry_run')->end()
            ->end()
        ;
    }
}
