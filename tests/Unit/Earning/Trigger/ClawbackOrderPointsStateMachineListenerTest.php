<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Earning\Trigger;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Earning\Trigger\ClawbackOrderPointsStateMachineListener;
use Setono\SyliusLoyaltyPlugin\Message\Command\ClawbackOrderPoints;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Marking;

final class ClawbackOrderPointsStateMachineListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function the_workflow_order_cancelled_hook_dispatches_a_clawback(): void
    {
        $order = $this->order(7);
        $commandBus = $this->expectClawbackDispatchedFor(7);

        $listener = new ClawbackOrderPointsStateMachineListener($commandBus);
        $listener->onWorkflowOrderCancelled(new CompletedEvent($order, new Marking()));
    }

    /**
     * @test
     */
    public function the_workflow_payment_refunded_hook_dispatches_a_clawback(): void
    {
        $order = $this->order(8);
        $commandBus = $this->expectClawbackDispatchedFor(8);

        $listener = new ClawbackOrderPointsStateMachineListener($commandBus);
        $listener->onWorkflowPaymentRefunded(new CompletedEvent($order, new Marking()));
    }

    /**
     * @test
     */
    public function the_winzou_hooks_dispatch_a_clawback(): void
    {
        $order = $this->order(9);
        $commandBus = $this->prophesize(MessageBusInterface::class);
        $commandBus->dispatch(Argument::that(static fn (ClawbackOrderPoints $command): bool => 9 === $command->order))
            ->willReturn(new Envelope(new \stdClass()))
            ->shouldBeCalledTimes(2);

        $listener = new ClawbackOrderPointsStateMachineListener($commandBus->reveal());
        $listener->onWinzouOrderCancelled($order);
        $listener->onWinzouPaymentRefunded($order);
    }

    /**
     * @test
     */
    public function the_workflow_hook_ignores_a_non_order_subject(): void
    {
        $commandBus = $this->prophesize(MessageBusInterface::class);
        $commandBus->dispatch(Argument::any())->shouldNotBeCalled();

        $listener = new ClawbackOrderPointsStateMachineListener($commandBus->reveal());
        $listener->onWorkflowOrderCancelled(new CompletedEvent(new \stdClass(), new Marking()));
    }

    private function order(int $id): OrderInterface
    {
        $order = $this->prophesize(OrderInterface::class);
        $order->getId()->willReturn($id);

        return $order->reveal();
    }

    private function expectClawbackDispatchedFor(int $orderId): MessageBusInterface
    {
        $commandBus = $this->prophesize(MessageBusInterface::class);
        $commandBus->dispatch(Argument::that(static fn (ClawbackOrderPoints $command): bool => $orderId === $command->order))
            ->willReturn(new Envelope(new \stdClass()))
            ->shouldBeCalled();

        return $commandBus->reveal();
    }
}
