<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Menu;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Menu\AccountMenuListener;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AccountMenuListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_adds_the_loyalty_item_to_the_account_menu(): void
    {
        $child = $this->prophesize(ItemInterface::class);
        $child->setLabel('setono_sylius_loyalty.ui.my_loyalty')->willReturn($child)->shouldBeCalled();
        $child->setLabelAttribute('icon', 'star')->willReturn($child)->shouldBeCalled();

        $menu = $this->prophesize(ItemInterface::class);
        $menu->addChild('setono_sylius_loyalty', ['route' => 'setono_sylius_loyalty_shop_account_loyalty'])
            ->willReturn($child)
            ->shouldBeCalled()
        ;

        $event = $this->prophesize(MenuBuilderEvent::class);
        $event->getMenu()->willReturn($menu);

        (new AccountMenuListener())->addItems($event->reveal());
    }
}
