<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Form\Type;

use Setono\SyliusLoyaltyPlugin\Form\Type\RedemptionType;
use Setono\SyliusLoyaltyPlugin\Tests\Application\Model\Order;
use Symfony\Component\Form\Test\TypeTestCase;

final class RedemptionTypeTest extends TypeTestCase
{
    /**
     * @test
     */
    public function it_maps_the_submitted_points_onto_the_order(): void
    {
        $order = new Order();

        $form = $this->factory->create(RedemptionType::class, $order);
        $form->submit(['loyaltyPointsRequested' => '500']);

        self::assertTrue($form->isSynchronized());
        self::assertSame(500, $order->getLoyaltyPointsRequested());
    }

    /**
     * @test
     */
    public function it_clamps_a_negative_submission_to_zero(): void
    {
        $order = new Order();

        $form = $this->factory->create(RedemptionType::class, $order);
        $form->submit(['loyaltyPointsRequested' => '-10']);

        self::assertTrue($form->isSynchronized());
        self::assertSame(0, $order->getLoyaltyPointsRequested());
    }

    /**
     * @test
     */
    public function it_leaves_the_points_at_zero_for_an_empty_submission(): void
    {
        $order = new Order();

        $form = $this->factory->create(RedemptionType::class, $order);
        $form->submit(['loyaltyPointsRequested' => '']);

        self::assertTrue($form->isSynchronized());
        self::assertSame(0, $order->getLoyaltyPointsRequested());
    }
}
