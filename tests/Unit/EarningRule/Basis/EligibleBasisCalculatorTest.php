<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\EarningRule\Basis;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\EarningRule\Basis\EligibleBasisCalculator;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgram;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;

final class EligibleBasisCalculatorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_computes_the_items_total_basis_excluding_taxes_by_default(): void
    {
        // item 1: 50.00 discounted with 10.00 tax; item 2: 30.00 with 6.00 tax
        $order = $this->order([1 => [5000, 1000], 2 => [3000, 600]], orderTotal: 9000, orderTaxTotal: 1600);

        $basis = (new EligibleBasisCalculator())->calculate($order, new LoyaltyProgram());

        self::assertSame([1 => 4000, 2 => 2400], $basis->itemAmounts);
        self::assertSame(0, $basis->extraAmount);
        self::assertSame(6400, $basis->getTotal());
    }

    /**
     * @test
     */
    public function it_includes_taxes_when_configured(): void
    {
        $order = $this->order([1 => [5000, 1000]], orderTotal: 5000, orderTaxTotal: 1000);

        $program = new LoyaltyProgram();
        $program->setIncludeTaxes(true);

        $basis = (new EligibleBasisCalculator())->calculate($order, $program);

        self::assertSame([1 => 5000], $basis->itemAmounts);
    }

    /**
     * @test
     */
    public function it_adds_the_non_item_remainder_under_the_order_total_basis(): void
    {
        // 80.00 of items (no taxes) + 15.00 shipping = 95.00 order total
        $order = $this->order([1 => [8000, 0]], orderTotal: 9500, orderTaxTotal: 0);

        $program = new LoyaltyProgram();
        $program->setEarningBasis(LoyaltyProgramInterface::EARNING_BASIS_ORDER_TOTAL);

        $basis = (new EligibleBasisCalculator())->calculate($order, $program);

        self::assertSame([1 => 8000], $basis->itemAmounts);
        self::assertSame(1500, $basis->extraAmount);
        self::assertSame(9500, $basis->getTotal());
    }

    /**
     * @param array<int, array{0: int, 1: int}> $items item id => [total, taxTotal]
     */
    private function order(array $items, int $orderTotal, int $orderTaxTotal): OrderInterface
    {
        $itemProphecies = [];
        foreach ($items as $id => [$total, $taxTotal]) {
            $item = $this->prophesize(OrderItemInterface::class);
            $item->getId()->willReturn($id);
            $item->getTotal()->willReturn($total);
            $item->getTaxTotal()->willReturn($taxTotal);
            $itemProphecies[] = $item->reveal();
        }

        $order = $this->prophesize(OrderInterface::class);
        $order->getItems()->willReturn(new ArrayCollection($itemProphecies));
        $order->getTotal()->willReturn($orderTotal);
        $order->getTaxTotal()->willReturn($orderTaxTotal);

        return $order->reveal();
    }
}
