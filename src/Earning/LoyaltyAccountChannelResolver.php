<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Earning;

use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

/**
 * Resolves the channels where the customer already holds a loyalty account.
 */
final class LoyaltyAccountChannelResolver implements TriggerChannelResolverInterface
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
