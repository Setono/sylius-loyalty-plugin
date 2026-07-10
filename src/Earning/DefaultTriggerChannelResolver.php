<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Earning;

use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

/**
 * Resolves the channels where the customer already holds a loyalty account. For a single-channel store
 * this is the one channel; a customer with no account yet earns nothing from context-less triggers
 * (they earn once they have an account, e.g. after their first order).
 */
final class DefaultTriggerChannelResolver implements TriggerChannelResolverInterface
{
    public function __construct(
        private readonly LoyaltyAccountRepositoryInterface $accountRepository,
    ) {
    }

    public function resolve(CustomerInterface $customer): iterable
    {
        foreach ($this->accountRepository->findByCustomer($customer) as $account) {
            $channel = $account->getChannel();
            if ($channel instanceof ChannelInterface) {
                yield $channel;
            }
        }
    }
}
