<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EventSubscriber;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AccountMenuSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.menu.shop.account' => 'addItems',
        ];
    }

    public function addItems(MenuBuilderEvent $event): void
    {
        $event->getMenu()
            ->addChild('setono_sylius_loyalty', ['route' => 'setono_sylius_loyalty_shop_account_loyalty'])
            ->setLabel('setono_sylius_loyalty.ui.my_loyalty')
            ->setLabelAttribute('icon', 'star')
        ;
    }
}
