<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Form\Extension;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Form\Extension\CartTypeExtension;
use Sylius\Bundle\OrderBundle\Form\Type\CartType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

final class CartTypeExtensionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_adds_the_loyalty_points_field_to_the_cart_form(): void
    {
        $builder = $this->prophesize(FormBuilderInterface::class);
        $builder->add(
            'loyaltyPointsRequested',
            IntegerType::class,
            Argument::allOf(
                Argument::withEntry('required', false),
                Argument::withEntry('empty_data', '0'),
            ),
        )->shouldBeCalled()->willReturn($builder->reveal());

        (new CartTypeExtension())->buildForm($builder->reveal(), []);
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
