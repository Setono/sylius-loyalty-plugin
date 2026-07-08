<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Rule\Basis;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Rule\Basis\EarningBasisProvider;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;

final class EarningBasisProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function items_total_basis_optionally_nets_out_item_tax(): void
    {
        self::assertSame(5000, (new EarningBasisProvider())->getBasis(
            $this->order(),
            $this->program(LoyaltyProgramInterface::EARNING_BASIS_ITEMS_TOTAL, includeTaxes: true),
        ));

        self::assertSame(4200, (new EarningBasisProvider())->getBasis(
            $this->order(),
            $this->program(LoyaltyProgramInterface::EARNING_BASIS_ITEMS_TOTAL, includeTaxes: false),
        ));
    }

    /**
     * @test
     */
    public function order_total_basis_optionally_nets_out_the_order_tax(): void
    {
        self::assertSame(6000, (new EarningBasisProvider())->getBasis(
            $this->order(),
            $this->program(LoyaltyProgramInterface::EARNING_BASIS_ORDER_TOTAL, includeTaxes: true),
        ));

        self::assertSame(5000, (new EarningBasisProvider())->getBasis(
            $this->order(),
            $this->program(LoyaltyProgramInterface::EARNING_BASIS_ORDER_TOTAL, includeTaxes: false),
        ));
    }

    private function order(): OrderInterface
    {
        $item = $this->prophesize(OrderItemInterface::class);
        $item->getAdjustmentsTotalRecursively(AdjustmentInterface::TAX_ADJUSTMENT)->willReturn(800);

        $order = $this->prophesize(OrderInterface::class);
        $order->getItemsTotal()->willReturn(5000);
        $order->getTotal()->willReturn(6000);
        $order->getTaxTotal()->willReturn(1000);
        $order->getItems()->willReturn(new ArrayCollection([$item->reveal()]));

        return $order->reveal();
    }

    private function program(string $earningBasis, bool $includeTaxes): LoyaltyProgramInterface
    {
        $program = $this->prophesize(LoyaltyProgramInterface::class);
        $program->getEarningBasis()->willReturn($earningBasis);
        $program->includeTaxes()->willReturn($includeTaxes);

        return $program->reveal();
    }
}
