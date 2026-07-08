<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Earning\Trigger;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Earning\Trigger\AwardOrderPointsStateMachineListener;
use Setono\SyliusLoyaltyPlugin\Earning\Trigger\AwardOrderPointsTriggerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Marking;

final class AwardOrderPointsStateMachineListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function the_workflow_payment_hook_triggers_with_the_payment_paid_moment(): void
    {
        $order = $this->prophesize(OrderInterface::class)->reveal();

        $trigger = $this->prophesize(AwardOrderPointsTriggerInterface::class);
        $trigger->trigger($order, LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_PAYMENT_PAID)->shouldBeCalled();

        $listener = new AwardOrderPointsStateMachineListener($trigger->reveal());
        $listener->onWorkflowPaymentPaid(new CompletedEvent($order, new Marking()));
    }

    /**
     * @test
     */
    public function the_workflow_order_hook_triggers_with_the_order_fulfilled_moment(): void
    {
        $order = $this->prophesize(OrderInterface::class)->reveal();

        $trigger = $this->prophesize(AwardOrderPointsTriggerInterface::class);
        $trigger->trigger($order, LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_ORDER_FULFILLED)->shouldBeCalled();

        $listener = new AwardOrderPointsStateMachineListener($trigger->reveal());
        $listener->onWorkflowOrderFulfilled(new CompletedEvent($order, new Marking()));
    }

    /**
     * @test
     */
    public function the_workflow_hook_ignores_a_non_order_subject(): void
    {
        $trigger = $this->prophesize(AwardOrderPointsTriggerInterface::class);
        $trigger->trigger(Argument::cetera())->shouldNotBeCalled();

        $listener = new AwardOrderPointsStateMachineListener($trigger->reveal());
        $listener->onWorkflowPaymentPaid(new CompletedEvent(new \stdClass(), new Marking()));
    }

    /**
     * @test
     */
    public function the_winzou_payment_hook_triggers_with_the_payment_paid_moment(): void
    {
        $order = $this->prophesize(OrderInterface::class)->reveal();

        $trigger = $this->prophesize(AwardOrderPointsTriggerInterface::class);
        $trigger->trigger($order, LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_PAYMENT_PAID)->shouldBeCalled();

        $listener = new AwardOrderPointsStateMachineListener($trigger->reveal());
        $listener->onWinzouPaymentPaid($order);
    }

    /**
     * @test
     */
    public function the_winzou_order_hook_triggers_with_the_order_fulfilled_moment(): void
    {
        $order = $this->prophesize(OrderInterface::class)->reveal();

        $trigger = $this->prophesize(AwardOrderPointsTriggerInterface::class);
        $trigger->trigger($order, LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_ORDER_FULFILLED)->shouldBeCalled();

        $listener = new AwardOrderPointsStateMachineListener($trigger->reveal());
        $listener->onWinzouOrderFulfilled($order);
    }
}
