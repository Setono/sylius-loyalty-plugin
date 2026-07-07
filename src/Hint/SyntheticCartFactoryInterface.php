<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Hint;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

interface SyntheticCartFactoryInterface
{
    /**
     * Null when the variant has no price on the channel.
     */
    public function create(ProductVariantInterface $variant, ChannelInterface $channel): ?OrderInterface;
}
