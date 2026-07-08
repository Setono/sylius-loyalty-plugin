<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyOrderTrait;

final class LoyaltyOrderTraitTest extends TestCase
{
    /**
     * @test
     */
    public function it_defaults_the_requested_points_to_zero(): void
    {
        $order = new class() {
            use LoyaltyOrderTrait;
        };

        self::assertSame(0, $order->getLoyaltyPointsRequested());
    }

    /**
     * @test
     */
    public function it_stores_the_requested_points(): void
    {
        $order = new class() {
            use LoyaltyOrderTrait;
        };

        $order->setLoyaltyPointsRequested(500);

        self::assertSame(500, $order->getLoyaltyPointsRequested());
    }

    /**
     * @test
     */
    public function it_clamps_a_negative_request_to_zero(): void
    {
        $order = new class() {
            use LoyaltyOrderTrait;
        };

        $order->setLoyaltyPointsRequested(-10);

        self::assertSame(0, $order->getLoyaltyPointsRequested());
    }
}
