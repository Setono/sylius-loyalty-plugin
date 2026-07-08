<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\DependencyInjection;

use Setono\SyliusLoyaltyPlugin\Earning\Trigger\AwardOrderPointsStateMachineListener;
use Setono\SyliusLoyaltyPlugin\Earning\Trigger\ClawbackOrderPointsStateMachineListener;
use Setono\SyliusLoyaltyPlugin\Redemption\RedemptionStateMachineListener;
use Setono\SyliusLoyaltyPlugin\Rule\Amount\EarningAmountInterface;
use Setono\SyliusLoyaltyPlugin\Rule\Condition\EarningConditionInterface;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Sylius\Component\Core\OrderPaymentTransitions;
use Sylius\Component\Order\OrderTransitions;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusLoyaltyExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{resources: array<string, mixed>} $config */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);

        $this->registerResources(
            'setono_sylius_loyalty',
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
            $config['resources'],
            $container,
        );

        // A project can add its own earning condition or amount just by implementing the interface —
        // the tag is applied automatically when the project has autoconfiguration enabled.
        $container->registerForAutoconfiguration(EarningConditionInterface::class)
            ->addTag('setono_sylius_loyalty.earning_condition');
        $container->registerForAutoconfiguration(EarningAmountInterface::class)
            ->addTag('setono_sylius_loyalty.earning_amount');

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->registerCommandBus($container);
        $this->registerWinzouCallbacks($container);
        $this->registerShopUi($container);
    }

    /**
     * Injects the cart redemption widget into the shop's cart summary.
     */
    private function registerShopUi(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('sylius_ui')) {
            return;
        }

        $container->prependExtensionConfig('sylius_ui', [
            'events' => [
                'sylius.shop.cart.summary' => [
                    'blocks' => [
                        'setono_sylius_loyalty_redemption' => [
                            'template' => '@SetonoSyliusLoyaltyPlugin/shop/cart/_redemption.html.twig',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * The plugin owns a dedicated command bus rather than reusing sylius.command_bus. The ledger manages
     * its own transaction boundaries (pessimistic locks inside wrapInTransaction), so it must not run
     * inside Sylius' command-bus doctrine_transaction middleware, which would wrap the whole handler in a
     * single transaction and hold row locks far longer than intended. A dedicated bus (default
     * middleware, no doctrine_transaction) also lets an application route the plugin's commands to an
     * async transport independently of Sylius' bus.
     */
    private function registerCommandBus(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('framework', [
            'messenger' => [
                'buses' => [
                    'setono_sylius_loyalty.command_bus' => [],
                ],
            ],
        ]);
    }

    /**
     * Registers the winzou callbacks that fire the award and clawback triggers. The symfony/workflow
     * counterparts are tagged in services.xml; both are always registered, and each only fires under the
     * adapter the application applies transitions through (the ledger's idempotency covers any overlap).
     */
    private function registerWinzouCallbacks(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('winzou_state_machine')) {
            return;
        }

        $award = '@' . AwardOrderPointsStateMachineListener::class;
        $clawback = '@' . ClawbackOrderPointsStateMachineListener::class;
        $redemption = '@' . RedemptionStateMachineListener::class;

        $container->prependExtensionConfig('winzou_state_machine', [
            OrderPaymentTransitions::GRAPH => [
                'callbacks' => [
                    'after' => [
                        'setono_sylius_loyalty_award_order_points' => [
                            'on' => [OrderPaymentTransitions::TRANSITION_PAY],
                            'do' => [$award, 'onWinzouPaymentPaid'],
                            'args' => ['object'],
                        ],
                        'setono_sylius_loyalty_clawback_order_points' => [
                            'on' => [OrderPaymentTransitions::TRANSITION_REFUND],
                            'do' => [$clawback, 'onWinzouPaymentRefunded'],
                            'args' => ['object'],
                        ],
                    ],
                ],
            ],
            OrderCheckoutTransitions::GRAPH => [
                'callbacks' => [
                    'after' => [
                        'setono_sylius_loyalty_redeem_order_points' => [
                            'on' => [OrderCheckoutTransitions::TRANSITION_COMPLETE],
                            'do' => [$redemption, 'onWinzouCheckoutCompleted'],
                            'args' => ['object'],
                        ],
                    ],
                ],
            ],
            OrderTransitions::GRAPH => [
                'callbacks' => [
                    'after' => [
                        'setono_sylius_loyalty_award_order_points' => [
                            'on' => [OrderTransitions::TRANSITION_FULFILL],
                            'do' => [$award, 'onWinzouOrderFulfilled'],
                            'args' => ['object'],
                        ],
                        'setono_sylius_loyalty_clawback_order_points' => [
                            'on' => [OrderTransitions::TRANSITION_CANCEL],
                            'do' => [$clawback, 'onWinzouOrderCancelled'],
                            'args' => ['object'],
                        ],
                        'setono_sylius_loyalty_rollback_redemption' => [
                            'on' => [OrderTransitions::TRANSITION_CANCEL],
                            'do' => [$redemption, 'onWinzouOrderCancelled'],
                            'args' => ['object'],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
