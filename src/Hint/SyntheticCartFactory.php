<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Hint;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

/**
 * Builds the in-memory one-item cart the product-page earn hint is evaluated against. Never
 * persisted; the rule engine's item claiming needs item ids, so a synthetic one is assigned.
 */
final class SyntheticCartFactory implements SyntheticCartFactoryInterface
{
    /**
     * @param FactoryInterface<OrderInterface> $orderFactory
     * @param FactoryInterface<OrderItemInterface> $orderItemFactory
     */
    public function __construct(
        private readonly FactoryInterface $orderFactory,
        private readonly FactoryInterface $orderItemFactory,
        private readonly OrderItemQuantityModifierInterface $quantityModifier,
    ) {
    }

    public function create(ProductVariantInterface $variant, ChannelInterface $channel): ?OrderInterface
    {
        $channelPricing = $variant->getChannelPricingForChannel($channel);
        if (null === $channelPricing || null === $channelPricing->getPrice()) {
            return null;
        }

        $order = $this->orderFactory->createNew();
        $order->setChannel($channel);
        $order->setCurrencyCode((string) $channel->getBaseCurrency()?->getCode());

        $item = $this->orderItemFactory->createNew();
        $item->setVariant($variant);
        $this->quantityModifier->modify($item, 1);
        $item->setUnitPrice($channelPricing->getPrice());
        $order->addItem($item);

        self::assignSyntheticId($item);

        return $order;
    }

    /**
     * The claiming pipeline correlates items and eligible amounts by item id, which in-memory
     * items lack.
     */
    private static function assignSyntheticId(OrderItemInterface $item): void
    {
        $reflection = new \ReflectionProperty($item, 'id');
        $reflection->setValue($item, 1);
        Assert::notNull($item->getId());
    }
}
