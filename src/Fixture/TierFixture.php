<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\AbstractResourceFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class TierFixture extends AbstractResourceFixture
{
    public function getName(): string
    {
        return 'setono_sylius_loyalty_tier';
    }

    protected function configureResourceNode(ArrayNodeDefinition $resourceNode): void
    {
        $resourceNode
            ->children()
                ->scalarNode('code')->cannotBeEmpty()->end()
                ->scalarNode('name')->cannotBeEmpty()->end()
                ->scalarNode('channel')->cannotBeEmpty()->end()
                ->integerNode('position')->end()
                ->booleanNode('enabled')->end()
                ->scalarNode('qualification_basis')->end()
                ->integerNode('threshold')->end()
                ->floatNode('earning_multiplier')->end()
                ->scalarNode('color')->end()
                ->scalarNode('benefits_description')->end()
            ->end()
        ;
    }
}
