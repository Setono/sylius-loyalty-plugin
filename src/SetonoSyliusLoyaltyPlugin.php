<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin;

use Setono\CompositeCompilerPass\CompositeCompilerPass;
use Setono\SyliusLoyaltyPlugin\EarningRule\Amount\AmountCalculatorRegistry;
use Setono\SyliusLoyaltyPlugin\EarningRule\Checker\ConditionCheckerRegistry;
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
            ConditionCheckerRegistry::class,
            'setono_sylius_loyalty.earning_condition',
        ));
        $container->addCompilerPass(new CompositeCompilerPass(
            AmountCalculatorRegistry::class,
            'setono_sylius_loyalty.earning_amount',
        ));
    }

    /**
     * @return list<string>
     */
    public function getSupportedDrivers(): array
    {
        return [SyliusResourceBundle::DRIVER_DOCTRINE_ORM];
    }
}
