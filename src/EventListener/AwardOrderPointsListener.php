<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EventListener;

use Setono\SyliusLoyaltyPlugin\Message\AwardOrderPoints;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;
use Symfony\Component\Workflow\Event\CompletedEvent;

/**
 * Dispatches the AwardOrderPoints message when the program's award moment is reached.
 * Registered on both state machine engines (winzou callbacks via the extension's prepended
 * config, symfony/workflow via event listener tags); the ledger's unique constraint makes
 * double execution a no-op.
 */
final class AwardOrderPointsListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly LoyaltyProgramProviderInterface $programProvider,
    ) {
    }

    /**
     * The winzou callback entry point for the sylius_order_payment graph's "pay" transition.
     */
    public function onOrderPaid(OrderInterface $order): void
    {
        $this->dispatch($order, LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_PAYMENT_PAID);
    }

    /**
     * The winzou callback entry point for the sylius_order graph's "fulfill" transition.
     */
    public function onOrderFulfilled(OrderInterface $order): void
    {
        $this->dispatch($order, LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_ORDER_FULFILLED);
    }

    public function onPaymentWorkflowCompleted(CompletedEvent $event): void
    {
        $order = $event->getSubject();
        if ($order instanceof OrderInterface) {
            $this->onOrderPaid($order);
        }
    }

    public function onOrderWorkflowCompleted(CompletedEvent $event): void
    {
        $order = $event->getSubject();
        if ($order instanceof OrderInterface) {
            $this->onOrderFulfilled($order);
        }
    }

    private function dispatch(OrderInterface $order, string $awardMoment): void
    {
        $channel = $order->getChannel();
        $orderId = $order->getId();

        if (null === $channel || !is_int($orderId) || null === $order->getCustomer()) {
            return;
        }

        if ($this->programProvider->getByChannel($channel)->getAwardOrderPointsAt() !== $awardMoment) {
            return;
        }

        $this->messageBus->dispatch(
            new Envelope(new AwardOrderPoints($orderId), [new DispatchAfterCurrentBusStamp()]),
        );
    }
}
