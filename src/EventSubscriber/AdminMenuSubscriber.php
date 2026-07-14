<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EventSubscriber;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AdminMenuSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.menu.admin.main' => 'addItems',
        ];
    }

    public function addItems(MenuBuilderEvent $event): void
    {
        $marketing = $event->getMenu()->getChild('marketing');
        if (null === $marketing) {
            return;
        }

        // A single "Loyalty" entry that opens the dashboard; admins navigate to the accounts grid and
        // the rest of the loyalty screens from there.
        $marketing
            ->addChild('setono_sylius_loyalty', ['route' => 'setono_sylius_loyalty_admin_dashboard'])
            ->setLabel('setono_sylius_loyalty.ui.loyalty')
            ->setLabelAttribute('icon', 'star')
        ;
    }
}
