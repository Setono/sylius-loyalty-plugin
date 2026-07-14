<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Earning;

use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

/**
 * Resolves the sole enabled channel when the store has exactly one — the unambiguous choice for a
 * single-channel store, regardless of the customer.
 */
final class SingleChannelResolver implements TriggerChannelResolverInterface
{
    /**
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
    ) {
    }

    public function resolve(CustomerInterface $customer): iterable
    {
        $channels = $this->channelRepository->findBy(['enabled' => true]);
        if (1 !== count($channels)) {
            return;
        }

        yield $channels[0];
    }
}
