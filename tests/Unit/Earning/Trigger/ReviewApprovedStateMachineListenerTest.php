<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Earning\Trigger;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Earning\ActionPointsAwarderInterface;
use Setono\SyliusLoyaltyPlugin\Earning\Trigger\ReviewApprovedStateMachineListener;
use Setono\SyliusLoyaltyPlugin\Earning\TriggerChannelResolverInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductReviewInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Marking;

final class ReviewApprovedStateMachineListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_awards_the_review_bonus_on_each_resolved_channel_via_the_workflow_adapter(): void
    {
        [$listener, $review] = $this->listenerAwarding();

        $listener->onWorkflowReviewAccepted(new CompletedEvent($review, new Marking()));
    }

    /**
     * @test
     */
    public function it_awards_the_review_bonus_via_the_winzou_adapter(): void
    {
        [$listener, $review] = $this->listenerAwarding();

        $listener->onWinzouReviewAccepted($review);
    }

    /**
     * @test
     */
    public function it_does_nothing_for_a_non_review_subject(): void
    {
        $awarder = $this->prophesize(ActionPointsAwarderInterface::class);
        $awarder->award(Argument::cetera())->shouldNotBeCalled();

        $listener = new ReviewApprovedStateMachineListener(
            $awarder->reveal(),
            $this->prophesize(TriggerChannelResolverInterface::class)->reveal(),
        );

        $listener->onWorkflowReviewAccepted(new CompletedEvent(new \stdClass(), new Marking()));
    }

    /**
     * @test
     */
    public function it_does_nothing_when_the_author_is_not_a_customer(): void
    {
        $review = $this->prophesize(ProductReviewInterface::class);
        $review->getAuthor()->willReturn(null);

        $awarder = $this->prophesize(ActionPointsAwarderInterface::class);
        $awarder->award(Argument::cetera())->shouldNotBeCalled();

        $listener = new ReviewApprovedStateMachineListener(
            $awarder->reveal(),
            $this->prophesize(TriggerChannelResolverInterface::class)->reveal(),
        );

        $listener->onWinzouReviewAccepted($review->reveal());
    }

    /**
     * @return array{ReviewApprovedStateMachineListener, ProductReviewInterface}
     */
    private function listenerAwarding(): array
    {
        $customer = $this->prophesize(CustomerInterface::class)->reveal();
        $channel = $this->prophesize(ChannelInterface::class);
        $channel->getId()->willReturn(1);
        $channel = $channel->reveal();

        $review = $this->prophesize(ProductReviewInterface::class);
        $review->getAuthor()->willReturn($customer);
        $review->getId()->willReturn(7);

        $resolver = $this->prophesize(TriggerChannelResolverInterface::class);
        $resolver->resolve($customer)->willReturn([$channel]);

        $awarder = $this->prophesize(ActionPointsAwarderInterface::class);
        $awarder->award($customer, $channel, 'product_review_approved', 'product_review_approved:7:1')->shouldBeCalled();

        return [new ReviewApprovedStateMachineListener($awarder->reveal(), $resolver->reveal()), $review->reveal()];
    }
}
