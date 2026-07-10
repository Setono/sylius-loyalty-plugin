<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Form\Extension;

use Setono\SyliusLoyaltyPlugin\Form\Extension\CartTypeExtension;
use Setono\SyliusLoyaltyPlugin\Tests\Application\Model\Order;
use Sylius\Bundle\OrderBundle\Form\Type\CartType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

final class CartTypeExtensionTest extends TypeTestCase
{
    /**
     * @return FormExtensionInterface[]
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [new CartType(Order::class)],
                [CartType::class => [new CartTypeExtension()]],
            ),
        ];
    }

    /**
     * @test
     */
    public function it_maps_the_submitted_points_onto_the_order(): void
    {
        $order = new Order();

        $form = $this->factory->create(CartType::class, $order);
        $form->submit(['items' => [], 'loyaltyPointsRequested' => '500']);

        self::assertTrue($form->isSynchronized());
        self::assertSame(500, $order->getLoyaltyPointsRequested());
    }

    /**
     * @test
     */
    public function it_treats_empty_input_as_zero(): void
    {
        $order = new Order();

        $form = $this->factory->create(CartType::class, $order);
        $form->submit(['items' => [], 'loyaltyPointsRequested' => '']);

        self::assertTrue($form->isSynchronized());
        self::assertSame(0, $order->getLoyaltyPointsRequested());
    }

    /**
     * @test
     */
    public function it_extends_the_cart_type(): void
    {
        $extendedTypes = [];
        foreach (CartTypeExtension::getExtendedTypes() as $type) {
            $extendedTypes[] = $type;
        }

        self::assertContains(CartType::class, $extendedTypes);
    }
}
