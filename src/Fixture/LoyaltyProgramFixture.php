<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Fixture;

use Sylius\Bundle\CoreBundle\Fixture\AbstractResourceFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class LoyaltyProgramFixture extends AbstractResourceFixture
{
    public function getName(): string
    {
        return 'setono_sylius_loyalty_program';
    }

    protected function configureResourceNode(ArrayNodeDefinition $resourceNode): void
    {
        $resourceNode
            ->children()
                ->scalarNode('channel')->cannotBeEmpty()->end()
                ->scalarNode('award_order_points_at')->end()
                ->scalarNode('earning_basis')->end()
                ->booleanNode('include_taxes')->end()
                ->scalarNode('rounding')->end()
                ->integerNode('redemption_conversion_points')->end()
                ->integerNode('redemption_conversion_amount')->end()
                ->integerNode('min_redeem_points')->end()
                ->integerNode('max_redeem_percent_of_order')->end()
                ->integerNode('points_expiry_days')->end()
                ->scalarNode('clawback_policy')->end()
                ->booleanNode('retroactive_guest_points')->end()
            ->end()
        ;
    }
}
