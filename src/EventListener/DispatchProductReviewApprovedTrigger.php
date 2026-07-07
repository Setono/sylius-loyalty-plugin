<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EventListener;

use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\SyliusLoyaltyPlugin\Event\Trigger\ProductReviewApprovedTriggerEvent;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Review\Model\ReviewInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

/**
 * Dogfoods the trigger mechanism: fires the built-in product_review_approved trigger when a
 * review passes the "accept" transition. Registered both as a winzou state machine callback
 * (the default engine) and as a symfony/workflow listener — the trigger's source identifier
 * ("review:<id>") makes double execution a no-op.
 */
final class DispatchProductReviewApprovedTrigger
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * The winzou callback entry point.
     */
    public function dispatch(ReviewInterface $review): void
    {
        $customer = $review->getAuthor();
        $reviewId = $review->getId();

        if (!$customer instanceof CustomerInterface || !is_int($reviewId)) {
            return;
        }

        $this->eventDispatcher->dispatch(new ProductReviewApprovedTriggerEvent($customer, $reviewId));
    }

    /**
     * The symfony/workflow entry point.
     */
    public function onWorkflowCompleted(CompletedEvent $event): void
    {
        $review = $event->getSubject();
        if ($review instanceof ReviewInterface) {
            $this->dispatch($review);
        }
    }
}
