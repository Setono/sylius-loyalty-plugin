<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Hint;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

interface EarnHintCalculatorInterface
{
    /**
     * "You will earn N points buying this product" — a synthetic one-item cart. Null hides
     * the hint (no price, no applicable rules, zero points, disabled account).
     */
    public function forVariant(ProductVariantInterface $variant, ChannelInterface $channel, ?CustomerInterface $customer): ?int;

    /**
     * "This order earns ~N points" — the real cart. Null hides the hint.
     */
    public function forCart(OrderInterface $cart, ?CustomerInterface $customer): ?int;
}
