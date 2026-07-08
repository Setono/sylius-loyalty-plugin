<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Redemption;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Redemption\OrderRedeemerInterface;
use Setono\SyliusLoyaltyPlugin\Redemption\RedemptionStateMachineListener;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Marking;

final class RedemptionStateMachineListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function the_workflow_checkout_hook_redeems(): void
    {
        $order = $this->prophesize(OrderInterface::class)->reveal();

        $redeemer = $this->prophesize(OrderRedeemerInterface::class);
        $redeemer->redeem($order)->shouldBeCalled();

        $listener = new RedemptionStateMachineListener($redeemer->reveal());
        $listener->onWorkflowCheckoutCompleted(new CompletedEvent($order, new Marking()));
    }

    /**
     * @test
     */
    public function the_workflow_cancel_hook_rolls_back(): void
    {
        $order = $this->prophesize(OrderInterface::class)->reveal();

        $redeemer = $this->prophesize(OrderRedeemerInterface::class);
        $redeemer->rollback($order)->shouldBeCalled();

        $listener = new RedemptionStateMachineListener($redeemer->reveal());
        $listener->onWorkflowOrderCancelled(new CompletedEvent($order, new Marking()));
    }

    /**
     * @test
     */
    public function the_winzou_hooks_redeem_and_roll_back(): void
    {
        $order = $this->prophesize(OrderInterface::class)->reveal();

        $redeemer = $this->prophesize(OrderRedeemerInterface::class);
        $redeemer->redeem($order)->shouldBeCalled();
        $redeemer->rollback($order)->shouldBeCalled();

        $listener = new RedemptionStateMachineListener($redeemer->reveal());
        $listener->onWinzouCheckoutCompleted($order);
        $listener->onWinzouOrderCancelled($order);
    }

    /**
     * @test
     */
    public function the_workflow_hook_ignores_a_non_order_subject(): void
    {
        $redeemer = $this->prophesize(OrderRedeemerInterface::class);
        $redeemer->redeem(Argument::cetera())->shouldNotBeCalled();

        $listener = new RedemptionStateMachineListener($redeemer->reveal());
        $listener->onWorkflowCheckoutCompleted(new CompletedEvent(new \stdClass(), new Marking()));
    }
}
