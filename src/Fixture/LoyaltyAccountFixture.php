<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\AbstractResourceFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class LoyaltyAccountFixture extends AbstractResourceFixture
{
    public function getName(): string
    {
        return 'setono_sylius_loyalty_account';
    }

    protected function configureResourceNode(ArrayNodeDefinition $resourceNode): void
    {
        $resourceNode
            ->children()
                ->scalarNode('email')->cannotBeEmpty()->end()
                ->scalarNode('channel')->cannotBeEmpty()->end()
                ->scalarNode('password')->end()
                ->booleanNode('enabled')->end()
                ->variableNode('history')->end()
            ->end()
        ;
    }
}
