<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Redemption;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Model\OrderInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Redemption\RedemptionCalculatorInterface;
use Setono\SyliusLoyaltyPlugin\Redemption\RedemptionOrderProcessor;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Order\Model\Adjustment;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class RedemptionOrderProcessorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_adds_a_redemption_adjustment_for_the_applied_points(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $customer = $this->prophesize(CustomerInterface::class)->reveal();

        $account = $this->prophesize(LoyaltyAccountInterface::class);
        $account->isEnabled()->willReturn(true);
        $account->getBalance()->willReturn(5000);

        $program = $this->prophesize(LoyaltyProgramInterface::class)->reveal();

        $accountProvider = $this->prophesize(LoyaltyAccountProviderInterface::class);
        $accountProvider->getAccount($customer, $channel)->willReturn($account->reveal());

        $programProvider = $this->prophesize(LoyaltyProgramProviderInterface::class);
        $programProvider->getProgram($channel)->willReturn($program);

        $calculator = $this->prophesize(RedemptionCalculatorInterface::class);
        $calculator->calculate(1000, 5000, 10000, $program)->willReturn(1000);
        $calculator->amount(1000, $program)->willReturn(1000);

        $adjustment = new Adjustment();
        $adjustmentFactory = $this->prophesize(FactoryInterface::class);
        $adjustmentFactory->createNew()->willReturn($adjustment);

        $order = $this->prophesize(OrderInterface::class);
        $order->getLoyaltyPointsRequested()->willReturn(1000);
        $order->getCustomer()->willReturn($customer);
        $order->getChannel()->willReturn($channel);
        $order->getItemsTotal()->willReturn(10000);
        $order->removeAdjustmentsRecursively(RedemptionOrderProcessor::ADJUSTMENT_TYPE)->shouldBeCalled();
        $order->addAdjustment($adjustment)->shouldBeCalled();

        $processor = new RedemptionOrderProcessor(
            $accountProvider->reveal(),
            $programProvider->reveal(),
            $calculator->reveal(),
            $adjustmentFactory->reveal(),
        );
        $processor->process($order->reveal());

        self::assertSame(RedemptionOrderProcessor::ADJUSTMENT_TYPE, $adjustment->getType());
        self::assertSame(-1000, $adjustment->getAmount());
        self::assertSame(['points' => 1000], $adjustment->getDetails());
    }

    /**
     * @test
     */
    public function it_only_clears_previous_adjustments_when_nothing_is_requested(): void
    {
        $calculator = $this->prophesize(RedemptionCalculatorInterface::class);
        $calculator->calculate(Argument::cetera())->shouldNotBeCalled();

        $order = $this->prophesize(OrderInterface::class);
        $order->getLoyaltyPointsRequested()->willReturn(0);
        $order->removeAdjustmentsRecursively(RedemptionOrderProcessor::ADJUSTMENT_TYPE)->shouldBeCalled();
        $order->addAdjustment(Argument::any())->shouldNotBeCalled();

        $processor = new RedemptionOrderProcessor(
            $this->prophesize(LoyaltyAccountProviderInterface::class)->reveal(),
            $this->prophesize(LoyaltyProgramProviderInterface::class)->reveal(),
            $calculator->reveal(),
            $this->prophesize(FactoryInterface::class)->reveal(),
        );
        $processor->process($order->reveal());
    }

    /**
     * @test
     */
    public function it_adds_no_adjustment_when_no_points_can_be_applied(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $customer = $this->prophesize(CustomerInterface::class)->reveal();

        $account = $this->prophesize(LoyaltyAccountInterface::class);
        $account->isEnabled()->willReturn(true);
        $account->getBalance()->willReturn(100);

        $program = $this->prophesize(LoyaltyProgramInterface::class)->reveal();

        $accountProvider = $this->prophesize(LoyaltyAccountProviderInterface::class);
        $accountProvider->getAccount($customer, $channel)->willReturn($account->reveal());

        $programProvider = $this->prophesize(LoyaltyProgramProviderInterface::class);
        $programProvider->getProgram($channel)->willReturn($program);

        $calculator = $this->prophesize(RedemptionCalculatorInterface::class);
        $calculator->calculate(1000, 100, 10000, $program)->willReturn(0);

        $adjustmentFactory = $this->prophesize(FactoryInterface::class);
        $adjustmentFactory->createNew()->shouldNotBeCalled();

        $order = $this->prophesize(OrderInterface::class);
        $order->getLoyaltyPointsRequested()->willReturn(1000);
        $order->getCustomer()->willReturn($customer);
        $order->getChannel()->willReturn($channel);
        $order->getItemsTotal()->willReturn(10000);
        $order->removeAdjustmentsRecursively(RedemptionOrderProcessor::ADJUSTMENT_TYPE)->shouldBeCalled();
        $order->addAdjustment(Argument::any())->shouldNotBeCalled();

        $processor = new RedemptionOrderProcessor(
            $accountProvider->reveal(),
            $programProvider->reveal(),
            $calculator->reveal(),
            $adjustmentFactory->reveal(),
        );
        $processor->process($order->reveal());
    }

    /**
     * @test
     */
    public function it_does_nothing_for_an_order_that_is_not_a_loyalty_order(): void
    {
        $order = $this->prophesize(\Sylius\Component\Order\Model\OrderInterface::class);
        $order->removeAdjustmentsRecursively(Argument::any())->shouldNotBeCalled();

        $this->processor()->process($order->reveal());
    }

    /**
     * @test
     */
    public function it_does_nothing_for_a_guest_order(): void
    {
        $order = $this->prophesize(OrderInterface::class);
        $order->getLoyaltyPointsRequested()->willReturn(1000);
        $order->getCustomer()->willReturn(null);
        $order->getChannel()->willReturn($this->prophesize(ChannelInterface::class)->reveal());
        $order->removeAdjustmentsRecursively(RedemptionOrderProcessor::ADJUSTMENT_TYPE)->shouldBeCalled();
        $order->addAdjustment(Argument::any())->shouldNotBeCalled();

        $this->processor()->process($order->reveal());
    }

    /**
     * @test
     */
    public function it_does_nothing_when_the_account_is_disabled(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $customer = $this->prophesize(CustomerInterface::class)->reveal();

        $account = $this->prophesize(LoyaltyAccountInterface::class);
        $account->isEnabled()->willReturn(false);

        $accountProvider = $this->prophesize(LoyaltyAccountProviderInterface::class);
        $accountProvider->getAccount($customer, $channel)->willReturn($account->reveal());

        $order = $this->prophesize(OrderInterface::class);
        $order->getLoyaltyPointsRequested()->willReturn(1000);
        $order->getCustomer()->willReturn($customer);
        $order->getChannel()->willReturn($channel);
        $order->removeAdjustmentsRecursively(RedemptionOrderProcessor::ADJUSTMENT_TYPE)->shouldBeCalled();
        $order->addAdjustment(Argument::any())->shouldNotBeCalled();

        $processor = new RedemptionOrderProcessor(
            $accountProvider->reveal(),
            $this->prophesize(LoyaltyProgramProviderInterface::class)->reveal(),
            $this->prophesize(RedemptionCalculatorInterface::class)->reveal(),
            $this->prophesize(FactoryInterface::class)->reveal(),
        );
        $processor->process($order->reveal());
    }

    private function processor(): RedemptionOrderProcessor
    {
        return new RedemptionOrderProcessor(
            $this->prophesize(LoyaltyAccountProviderInterface::class)->reveal(),
            $this->prophesize(LoyaltyProgramProviderInterface::class)->reveal(),
            $this->prophesize(RedemptionCalculatorInterface::class)->reveal(),
            $this->prophesize(FactoryInterface::class)->reveal(),
        );
    }
}
