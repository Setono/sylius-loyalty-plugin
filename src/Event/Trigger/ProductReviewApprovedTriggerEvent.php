<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event\Trigger;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

final class ProductReviewApprovedTriggerEvent extends EarningTriggerEvent
{
    public function __construct(
        CustomerInterface $customer,
        public readonly int $reviewId,
        ?ChannelInterface $channel = null,
    ) {
        parent::__construct($customer, $channel, sprintf('review:%d', $reviewId));
    }

    public static function getTriggerCode(): string
    {
        return 'product_review_approved';
    }

    public static function getLabel(): string
    {
        return 'setono_sylius_loyalty.trigger.product_review_approved';
    }
}
