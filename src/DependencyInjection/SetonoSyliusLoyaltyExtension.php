<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\DependencyInjection;

use Setono\SyliusLoyaltyPlugin\EarningRule\Amount\AmountCalculatorInterface;
use Setono\SyliusLoyaltyPlugin\EarningRule\Checker\ConditionCheckerInterface;
use Setono\SyliusLoyaltyPlugin\Expression\Function\ExpressionFunctionInterface;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusLoyaltyExtension extends AbstractResourceExtension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /**
         * @var array{
         *     resources: array<string, mixed>,
         *     manual_adjustment_reasons: list<string>,
         *     triggers: list<class-string>,
         *     transaction_types: array<string, class-string>,
         *     expression_editor: array{cdn_base_url: string},
         * } $config
         */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter('setono_sylius_loyalty.manual_adjustment_reasons', $config['manual_adjustment_reasons']);
        $container->setParameter('setono_sylius_loyalty.triggers', $config['triggers']);
        $container->setParameter('setono_sylius_loyalty.transaction_types', $config['transaction_types']);
        $container->setParameter('setono_sylius_loyalty.expression_editor.cdn_base_url', $config['expression_editor']['cdn_base_url']);

        $this->registerResources(
            'setono_sylius_loyalty',
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
            $config['resources'],
            $container,
        );

        $loader->load('services.xml');

        $container->registerForAutoconfiguration(ConditionCheckerInterface::class)
            ->addTag('setono_sylius_loyalty.earning_condition');
        $container->registerForAutoconfiguration(AmountCalculatorInterface::class)
            ->addTag('setono_sylius_loyalty.earning_amount');
        $container->registerForAutoconfiguration(ExpressionFunctionInterface::class)
            ->addTag('setono_sylius_loyalty.expression_function');
    }
}
