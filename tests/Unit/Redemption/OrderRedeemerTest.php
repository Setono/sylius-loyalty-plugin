<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Redemption;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Redemption\OrderRedeemer;
use Setono\SyliusLoyaltyPlugin\Redemption\RedemptionOrderProcessor;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class OrderRedeemerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_redeems_the_points_recorded_in_the_redemption_adjustment(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $customer = $this->prophesize(CustomerInterface::class)->reveal();
        $account = $this->prophesize(LoyaltyAccountInterface::class)->reveal();

        $adjustment = $this->prophesize(AdjustmentInterface::class);
        $adjustment->getDetails()->willReturn(['points' => 300]);

        $order = $this->prophesize(OrderInterface::class);
        $order->getCustomer()->willReturn($customer);
        $order->getChannel()->willReturn($channel);
        $order->getAdjustments(RedemptionOrderProcessor::ADJUSTMENT_TYPE)->willReturn(new ArrayCollection([$adjustment->reveal()]));
        $revealed = $order->reveal();

        $accountProvider = $this->prophesize(LoyaltyAccountProviderInterface::class);
        $accountProvider->getAccount($customer, $channel)->willReturn($account);

        $ledger = $this->prophesize(LoyaltyLedgerInterface::class);
        $ledger->redeem($account, $revealed, 300)->shouldBeCalled();

        (new OrderRedeemer($accountProvider->reveal(), $ledger->reveal()))->redeem($revealed);
    }

    /**
     * @test
     */
    public function it_does_not_redeem_a_guest_order(): void
    {
        $order = $this->prophesize(OrderInterface::class);
        $order->getCustomer()->willReturn(null);
        $order->getChannel()->willReturn($this->prophesize(ChannelInterface::class)->reveal());

        $ledger = $this->prophesize(LoyaltyLedgerInterface::class);
        $ledger->redeem(Argument::cetera())->shouldNotBeCalled();

        (new OrderRedeemer($this->prophesize(LoyaltyAccountProviderInterface::class)->reveal(), $ledger->reveal()))
            ->redeem($order->reveal());
    }

    /**
     * @test
     */
    public function it_does_not_redeem_when_there_is_no_redemption_adjustment(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $customer = $this->prophesize(CustomerInterface::class)->reveal();

        $order = $this->prophesize(OrderInterface::class);
        $order->getCustomer()->willReturn($customer);
        $order->getChannel()->willReturn($channel);
        $order->getAdjustments(RedemptionOrderProcessor::ADJUSTMENT_TYPE)->willReturn(new ArrayCollection([]));

        $ledger = $this->prophesize(LoyaltyLedgerInterface::class);
        $ledger->redeem(Argument::cetera())->shouldNotBeCalled();

        (new OrderRedeemer($this->prophesize(LoyaltyAccountProviderInterface::class)->reveal(), $ledger->reveal()))
            ->redeem($order->reveal());
    }

    /**
     * @test
     */
    public function it_rolls_the_redemption_back(): void
    {
        $order = $this->prophesize(OrderInterface::class)->reveal();

        $ledger = $this->prophesize(LoyaltyLedgerInterface::class);
        $ledger->rollbackRedemption($order)->shouldBeCalled();

        (new OrderRedeemer($this->prophesize(LoyaltyAccountProviderInterface::class)->reveal(), $ledger->reveal()))
            ->rollback($order);
    }
}
