<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin;

use Setono\CompositeCompilerPass\CompositeCompilerPass;
use Setono\SyliusLoyaltyPlugin\Earning\CompositeTriggerChannelResolver;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Sylius\Bundle\ResourceBundle\AbstractResourceBundle;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SetonoSyliusLoyaltyPlugin extends AbstractResourceBundle
{
    use SyliusPluginTrait;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CompositeCompilerPass(
            CompositeTriggerChannelResolver::class,
            'setono_sylius_loyalty.trigger_channel_resolver',
            'add',
        ));
    }

    /**
     * @return list<string>
     */
    public function getSupportedDrivers(): array
    {
        return [
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
        ];
    }
}
