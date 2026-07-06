<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class ShopAccountMenuListener
{
    public function __invoke(MenuBuilderEvent $event): void
    {
        $event->getMenu()
            ->addChild('setono_sylius_loyalty', ['route' => 'setono_sylius_loyalty_shop_account_loyalty'])
            ->setLabel('setono_sylius_loyalty.ui.my_loyalty')
            ->setLabelAttribute('icon', 'star')
        ;
    }
}
