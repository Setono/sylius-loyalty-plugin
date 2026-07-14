<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Grid;

use Sylius\Bundle\GridBundle\Builder\Action\ShowAction;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\ItemActionGroup;
use Sylius\Bundle\GridBundle\Builder\Field\DateTimeField;
use Sylius\Bundle\GridBundle\Builder\Field\StringField;
use Sylius\Bundle\GridBundle\Builder\Field\TwigField;
use Sylius\Bundle\GridBundle\Builder\Filter\BooleanFilter;
use Sylius\Bundle\GridBundle\Builder\Filter\EntityFilter;
use Sylius\Bundle\GridBundle\Builder\Filter\StringFilter;
use Sylius\Bundle\GridBundle\Grid\AbstractGrid;
use Sylius\Bundle\GridBundle\Grid\ResourceAwareGridInterface;
use Sylius\Component\Grid\Builder\GridBuilderInterface;

/**
 * Read-only admin oversight of loyalty accounts: balances are derived from the append-only ledger, so
 * the grid lists rather than edits them. One row per (customer, channel) account.
 */
final class LoyaltyAccountGrid extends AbstractGrid implements ResourceAwareGridInterface
{
    /**
     * @param class-string $resourceClass
     * @param class-string $channelClass
     */
    public function __construct(
        private readonly string $resourceClass,
        private readonly string $channelClass,
    ) {
    }

    public static function getName(): string
    {
        return 'setono_sylius_loyalty_admin_account';
    }

    public function buildGrid(GridBuilderInterface $gridBuilder): void
    {
        $gridBuilder
            ->setLimits([25, 50, 100])
            ->orderBy('createdAt', 'desc')
            ->addField(
                StringField::create('customer')
                    ->setLabel('sylius.ui.customer')
                    ->setPath('customer.email'),
            )
            ->addField(
                StringField::create('channel')
                    ->setLabel('sylius.ui.channel')
                    ->setPath('channel.code'),
            )
            ->addField(
                StringField::create('balance')
                    ->setLabel('setono_sylius_loyalty.ui.balance')
                    ->setSortable(true),
            )
            ->addField(
                StringField::create('lifetimeEarned')
                    ->setLabel('setono_sylius_loyalty.ui.lifetime_earned')
                    ->setSortable(true),
            )
            ->addField(
                TwigField::create('enabled', '@SetonoSyliusLoyaltyPlugin/admin/loyalty_account/grid/field/enabled.html.twig')
                    ->setLabel('sylius.ui.enabled')
                    ->setPath('.'),
            )
            ->addField(
                DateTimeField::create('createdAt', 'd-m-Y H:i')
                    ->setLabel('sylius.ui.created_at')
                    ->setSortable(true),
            )
            ->addFilter(
                StringFilter::create('customer', ['customer.email']),
            )
            ->addFilter(
                EntityFilter::create('channel', $this->channelClass),
            )
            ->addFilter(
                BooleanFilter::create('enabled'),
            )
            ->addActionGroup(
                ItemActionGroup::create(
                    ShowAction::create([
                        'link' => [
                            'route' => 'setono_sylius_loyalty_admin_account_show',
                            'parameters' => ['id' => 'resource.id'],
                        ],
                    ]),
                ),
            )
        ;
    }

    public function getResourceClass(): string
    {
        return $this->resourceClass;
    }
}
