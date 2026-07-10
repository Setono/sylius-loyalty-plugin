<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Earning;

use Setono\CompositeCompilerPass\CompositeService;
use Sylius\Component\Core\Model\CustomerInterface;

/**
 * Merges the channels returned by every tagged resolver, de-duplicated by channel code. Tag a resolver
 * with `setono_sylius_loyalty.trigger_channel_resolver` (autoconfigured from the interface) to
 * contribute channels.
 *
 * @extends CompositeService<TriggerChannelResolverInterface>
 */
final class CompositeTriggerChannelResolver extends CompositeService implements TriggerChannelResolverInterface
{
    public function resolve(CustomerInterface $customer): iterable
    {
        $seen = [];
        foreach ($this->services as $resolver) {
            foreach ($resolver->resolve($customer) as $channel) {
                $code = (string) $channel->getCode();
                if (isset($seen[$code])) {
                    continue;
                }

                $seen[$code] = true;

                yield $channel;
            }
        }
    }
}
