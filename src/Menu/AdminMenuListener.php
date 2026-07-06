<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

// todo: Convert this to an event subscriber and put it inside src/EventSubscriber
/**
 * The plugin's single admin menu entry: "Loyalty" under Marketing, opening the loyalty
 * dashboard from which everything else is reached.
 */
final class AdminMenuListener
{
    public function __invoke(MenuBuilderEvent $event): void
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
