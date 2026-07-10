<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Earning;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

interface ActionPointsAwarderInterface
{
    /**
     * Evaluates the channel's enabled rules for the given trigger against a customer action (there is
     * no order) and credits the resulting points to the customer's account for that channel. Only
     * order-independent rules contribute — a fixed amount earns, a per-amount rule yields nothing
     * because the basis is zero. The write is idempotent on the source identifier.
     */
    public function award(
        CustomerInterface $customer,
        ChannelInterface $channel,
        string $trigger,
        string $sourceIdentifier,
        ?\DateTimeInterface $awardedAt = null,
    ): void;
}
