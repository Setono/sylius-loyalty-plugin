<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\DependencyInjection;

use Setono\SyliusLoyaltyPlugin\EarningRule\Amount\AmountCalculatorInterface;
use Setono\SyliusLoyaltyPlugin\EarningRule\Checker\ConditionCheckerInterface;
use Setono\SyliusLoyaltyPlugin\Expression\Function\ExpressionFunctionInterface;
use Setono\SyliusLoyaltyPlugin\Tier\QualificationBasis\TierQualificationBasisInterface;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusLoyaltyExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        $this->prependWinzouStateMachineConfig($container);
        $this->prependSyliusUiConfig($container);
        $this->prependSyliusGridConfig($container);

        if ($container->hasExtension('twig')) {
            $container->prependExtensionConfig('twig', [
                'form_themes' => ['@SetonoSyliusLoyaltyPlugin/form/theme.html.twig'],
            ]);
        }
    }

    /**
     * Registers the plugin's admin grids.
     */
    private function prependSyliusGridConfig(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('sylius_grid')) {
            return;
        }

        $container->prependExtensionConfig('sylius_grid', [
            'grids' => [
                'setono_sylius_loyalty_admin_account' => [
                    'driver' => [
                        'name' => 'doctrine/orm',
                        'options' => ['class' => '%setono_sylius_loyalty.model.account.class%'],
                    ],
                    'sorting' => ['balance' => 'desc'],
                    'fields' => [
                        'customer' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.customer',
                            'path' => 'customer.email',
                            'sortable' => 'customer.email',
                        ],
                        'channel' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.channel',
                            'path' => 'channel.code',
                        ],
                        'balance' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_loyalty.ui.balance_label',
                            'sortable' => 'balance',
                        ],
                        'lifetimeEarned' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_loyalty.ui.lifetime_earned',
                            'sortable' => 'lifetimeEarned',
                        ],
                        'tier' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_loyalty.ui.tier',
                            'path' => 'tier.name',
                        ],
                        'enabled' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.enabled',
                            'sortable' => 'enabled',
                            'options' => ['template' => '@SyliusUi/Grid/Field/yesNo.html.twig'],
                        ],
                    ],
                    'filters' => [
                        'customer' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.customer',
                            'options' => ['fields' => ['customer.email']],
                        ],
                        'enabled' => [
                            'type' => 'boolean',
                            'label' => 'sylius.ui.enabled',
                        ],
                    ],
                    'actions' => [
                        'item' => [
                            'inspect' => [
                                'type' => 'show',
                                'label' => 'setono_sylius_loyalty.ui.inspect',
                                'options' => [
                                    'link' => [
                                        'route' => 'setono_sylius_loyalty_admin_account_inspect',
                                        'parameters' => ['id' => 'resource.id'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'setono_sylius_loyalty_admin_earning_rule' => [
                    'driver' => [
                        'name' => 'doctrine/orm',
                        'options' => ['class' => '%setono_sylius_loyalty.model.earning_rule.class%'],
                    ],
                    'sorting' => ['priority' => 'desc'],
                    'fields' => [
                        'name' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.name',
                            'sortable' => 'name',
                        ],
                        'trigger' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_loyalty.ui.trigger',
                            'sortable' => 'trigger',
                        ],
                        'scope' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_loyalty.ui.scope',
                        ],
                        'priority' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.priority',
                            'sortable' => 'priority',
                        ],
                        'enabled' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.enabled',
                            'sortable' => 'enabled',
                            'options' => ['template' => '@SyliusUi/Grid/Field/yesNo.html.twig'],
                        ],
                        'dryRun' => [
                            'type' => 'twig',
                            'label' => 'setono_sylius_loyalty.ui.dry_run',
                            'sortable' => 'dryRun',
                            'options' => ['template' => '@SyliusUi/Grid/Field/yesNo.html.twig'],
                        ],
                    ],
                    'filters' => [
                        'name' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.name',
                            'options' => ['fields' => ['name']],
                        ],
                        'enabled' => [
                            'type' => 'boolean',
                            'label' => 'sylius.ui.enabled',
                        ],
                    ],
                    'actions' => [
                        'main' => [
                            'create' => ['type' => 'create'],
                        ],
                        'item' => [
                            'update' => ['type' => 'update'],
                            'delete' => ['type' => 'delete'],
                        ],
                    ],
                ],
                'setono_sylius_loyalty_admin_tier' => [
                    'driver' => [
                        'name' => 'doctrine/orm',
                        'options' => ['class' => '%setono_sylius_loyalty.model.tier.class%'],
                    ],
                    'sorting' => ['position' => 'desc'],
                    'fields' => [
                        'name' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.name',
                            'sortable' => 'name',
                        ],
                        'code' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.code',
                            'sortable' => 'code',
                        ],
                        'channel' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.channel',
                            'path' => 'channel.code',
                        ],
                        'position' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.position',
                            'sortable' => 'position',
                        ],
                        'threshold' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_loyalty.form.tier.threshold',
                            'sortable' => 'threshold',
                        ],
                        'earningMultiplier' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_loyalty.form.tier.earning_multiplier',
                        ],
                        'enabled' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.enabled',
                            'sortable' => 'enabled',
                            'options' => ['template' => '@SyliusUi/Grid/Field/yesNo.html.twig'],
                        ],
                    ],
                    'actions' => [
                        'main' => [
                            'create' => ['type' => 'create'],
                        ],
                        'item' => [
                            'update' => ['type' => 'update'],
                            'delete' => ['type' => 'delete'],
                        ],
                    ],
                ],
                'setono_sylius_loyalty_admin_dry_run_result' => [
                    'driver' => [
                        'name' => 'doctrine/orm',
                        'options' => ['class' => '%setono_sylius_loyalty.model.dry_run_result.class%'],
                    ],
                    'sorting' => ['createdAt' => 'desc'],
                    'fields' => [
                        'rule' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_loyalty.ui.rule',
                            'path' => 'rule.name',
                        ],
                        'account' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.customer',
                            'path' => 'account.customer.email',
                        ],
                        'points' => [
                            'type' => 'string',
                            'label' => 'setono_sylius_loyalty.ui.points',
                            'sortable' => 'points',
                        ],
                        'createdAt' => [
                            'type' => 'datetime',
                            'label' => 'sylius.ui.date',
                            'sortable' => 'createdAt',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Registers the plugin's template-event blocks.
     */
    private function prependSyliusUiConfig(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('sylius_ui')) {
            return;
        }

        $container->prependExtensionConfig('sylius_ui', [
            'events' => [
                'sylius.shop.cart.summary' => [
                    'blocks' => [
                        'setono_sylius_loyalty_cart_earn_hint' => [
                            'template' => '@SetonoSyliusLoyaltyPlugin/shop/cart/_earn_hint.html.twig',
                            'priority' => 3,
                        ],
                        'setono_sylius_loyalty_redemption' => [
                            'template' => '@SetonoSyliusLoyaltyPlugin/shop/cart/_redemption.html.twig',
                            'priority' => 5,
                        ],
                    ],
                ],
                'sylius.shop.checkout.complete.summary' => [
                    'blocks' => [
                        'setono_sylius_loyalty_redemption_summary' => [
                            'template' => '@SetonoSyliusLoyaltyPlugin/shop/checkout/_redemption_summary.html.twig',
                            'priority' => 5,
                        ],
                    ],
                ],
                'sylius.shop.product.show.add_to_cart_form' => [
                    'blocks' => [
                        // Deviation from the spec's "directly below the add-to-cart button":
                        // Sylius 1.14 has no template event there, so the hint renders just
                        // above the button instead
                        'setono_sylius_loyalty_earn_hint' => [
                            'template' => '@SetonoSyliusLoyaltyPlugin/shop/product/_earn_hint.html.twig',
                            'priority' => -5,
                        ],
                    ],
                ],
                'sylius.admin.customer.show.content' => [
                    'blocks' => [
                        'setono_sylius_loyalty_customer_loyalty' => [
                            'template' => '@SetonoSyliusLoyaltyPlugin/admin/customer/_loyalty.html.twig',
                            'priority' => -10,
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        /**
         * @var array{
         *     resources: array<string, mixed>,
         *     manual_adjustment_reasons: list<string>,
         *     triggers: list<class-string>,
         *     transaction_types: array<string, class-string>,
         *     expression_editor: array{cdn_base_url: string},
         *     retain_anonymized_ledger: bool,
         * } $config
         */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter('setono_sylius_loyalty.manual_adjustment_reasons', $config['manual_adjustment_reasons']);
        $container->setParameter('setono_sylius_loyalty.triggers', $config['triggers']);
        $container->setParameter('setono_sylius_loyalty.transaction_types', $config['transaction_types']);
        $container->setParameter('setono_sylius_loyalty.expression_editor.cdn_base_url', $config['expression_editor']['cdn_base_url']);
        $container->setParameter('setono_sylius_loyalty.retain_anonymized_ledger', $config['retain_anonymized_ledger']);

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
        $container->registerForAutoconfiguration(TierQualificationBasisInterface::class)
            ->addTag('setono_sylius_loyalty.tier_qualification_basis');
    }

    /**
     * Registers the plugin's state machine callbacks on the winzou engine (still Sylius'
     * default graph engine). The symfony/workflow counterparts are plain event listener tags;
     * database-level idempotency makes registering both engines safe.
     */
    private function prependWinzouStateMachineConfig(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('winzou_state_machine')) {
            return;
        }

        $container->prependExtensionConfig('winzou_state_machine', [
            'sylius_product_review' => [
                'callbacks' => [
                    'after' => [
                        'setono_sylius_loyalty_dispatch_review_trigger' => [
                            'on' => ['accept'],
                            'do' => ['@Setono\SyliusLoyaltyPlugin\EventListener\DispatchProductReviewApprovedTrigger', 'dispatch'],
                            'args' => ['object'],
                        ],
                    ],
                ],
            ],
            'sylius_order_payment' => [
                'callbacks' => [
                    'after' => [
                        'setono_sylius_loyalty_award_order_points' => [
                            'on' => ['pay'],
                            'do' => ['@Setono\SyliusLoyaltyPlugin\EventListener\AwardOrderPointsListener', 'onOrderPaid'],
                            'args' => ['object'],
                        ],
                        'setono_sylius_loyalty_clawback_on_refund' => [
                            'on' => ['refund'],
                            'do' => ['@Setono\SyliusLoyaltyPlugin\EventListener\ClawbackListener', 'clawback'],
                            'args' => ['object'],
                        ],
                    ],
                ],
            ],
            'sylius_order' => [
                'callbacks' => [
                    'after' => [
                        'setono_sylius_loyalty_award_order_points_fulfilled' => [
                            'on' => ['fulfill'],
                            'do' => ['@Setono\SyliusLoyaltyPlugin\EventListener\AwardOrderPointsListener', 'onOrderFulfilled'],
                            'args' => ['object'],
                        ],
                        'setono_sylius_loyalty_rollback_redemption' => [
                            'on' => ['cancel'],
                            'do' => ['@Setono\SyliusLoyaltyPlugin\EventListener\RollbackRedemptionListener', 'rollback'],
                            'args' => ['object'],
                        ],
                        'setono_sylius_loyalty_clawback_on_cancel' => [
                            'on' => ['cancel'],
                            'do' => ['@Setono\SyliusLoyaltyPlugin\EventListener\ClawbackListener', 'clawback'],
                            'args' => ['object'],
                        ],
                    ],
                ],
            ],
            'sylius_order_checkout' => [
                'callbacks' => [
                    // A "before" callback so a failed debit aborts the completion
                    'before' => [
                        'setono_sylius_loyalty_redeem_points' => [
                            'on' => ['complete'],
                            'do' => ['@Setono\SyliusLoyaltyPlugin\EventListener\RedeemPointsListener', 'redeem'],
                            'args' => ['object'],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
