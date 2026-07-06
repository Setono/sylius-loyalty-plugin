<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin;

use Setono\CompositeCompilerPass\CompositeCompilerPass;
use Setono\SyliusLoyaltyPlugin\DependencyInjection\Compiler\RegisterEarningTriggersPass;
use Setono\SyliusLoyaltyPlugin\EarningRule\Amount\AmountCalculatorRegistry;
use Setono\SyliusLoyaltyPlugin\EarningRule\Checker\ConditionCheckerRegistry;
use Setono\SyliusLoyaltyPlugin\Expression\Function\ExpressionFunctionRegistry;
use Setono\SyliusLoyaltyPlugin\Tier\QualificationBasis\TierQualificationBasisRegistry;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Sylius\Bundle\ResourceBundle\AbstractResourceBundle;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
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
        $container->addCompilerPass(new CompositeCompilerPass(
            ExpressionFunctionRegistry::class,
            'setono_sylius_loyalty.expression_function',
        ));
        $container->addCompilerPass(new CompositeCompilerPass(
            TierQualificationBasisRegistry::class,
            'setono_sylius_loyalty.tier_qualification_basis',
        ));

        // Must run before Symfony's RegisterListenersPass (same phase, priority 0) so the
        // trigger listener tags it adds are picked up
        $container->addCompilerPass(new RegisterEarningTriggersPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 10);
    }

    /**
     * @return list<string>
     */
    public function getSupportedDrivers(): array
    {
        return [SyliusResourceBundle::DRIVER_DOCTRINE_ORM];
    }
}
