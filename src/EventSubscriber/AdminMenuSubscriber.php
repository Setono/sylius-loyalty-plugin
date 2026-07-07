<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EventSubscriber;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The plugin's single admin menu entry: "Loyalty" under Marketing, opening the loyalty
 * dashboard from which everything else is reached.
 */
final class AdminMenuSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.menu.admin.main' => 'addLoyaltyMenuItem',
        ];
    }

    public function addLoyaltyMenuItem(MenuBuilderEvent $event): void
    {
        $marketing = $event->getMenu()->getChild('marketing');
        if (null === $marketing) {
            return;
        }

        $marketing
            ->addChild('setono_sylius_loyalty', ['route' => 'setono_sylius_loyalty_admin_dashboard'])
            ->setLabel('setono_sylius_loyalty.ui.loyalty')
            ->setLabelAttribute('icon', 'star')
        ;
    }
}
