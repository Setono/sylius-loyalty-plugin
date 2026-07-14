<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Earning\Trigger;

use Setono\SyliusLoyaltyPlugin\Earning\ActionPointsAwarderInterface;
use Setono\SyliusLoyaltyPlugin\Earning\TriggerChannelResolverInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Review\Model\ReviewInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

/**
 * Awards the `product_review_approved` rules when a product review is accepted. Approval happens in
 * admin (no shop channel), so the channels are resolved from the author's loyalty accounts. Bridges
 * both state-machine adapters, like the order-award listener; the ledger's idempotency covers overlap.
 */
final class ReviewApprovedStateMachineListener
{
    public const TRIGGER = 'product_review_approved';

    public function __construct(
        private readonly ActionPointsAwarderInterface $awarder,
        private readonly TriggerChannelResolverInterface $channelResolver,
    ) {
    }

    public function onWorkflowReviewAccepted(CompletedEvent $event): void
    {
        $subject = $event->getSubject();
        if ($subject instanceof ReviewInterface) {
            $this->award($subject);
        }
    }

    public function onWinzouReviewAccepted(ReviewInterface $review): void
    {
        $this->award($review);
    }

    private function award(ReviewInterface $review): void
    {
        $author = $review->getAuthor();
        if (!$author instanceof CustomerInterface) {
            return;
        }

        $reviewId = (int) $review->getId();
        foreach ($this->channelResolver->resolve($author) as $channel) {
            $this->awarder->award(
                $author,
                $channel,
                self::TRIGGER,
                sprintf('%s:%d:%d', self::TRIGGER, $reviewId, (int) $channel->getId()),
            );
        }
    }
}
