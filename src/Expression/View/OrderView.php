<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression\View;

use Sylius\Component\Core\Model\OrderInterface;

final class OrderView
{
    private function __construct(
        public readonly int $total,
        public readonly int $itemsTotal,
        public readonly int $shippingTotal,
        public readonly string $number,
        public readonly string $currencyCode,
        public readonly ?CustomerView $customer,
        public readonly ?ChannelView $channel,
    ) {
    }

    public static function fromOrder(OrderInterface $order): self
    {
        $customer = $order->getCustomer();
        $channel = $order->getChannel();

        return new self(
            $order->getTotal(),
            $order->getItemsTotal(),
            $order->getShippingTotal(),
            (string) $order->getNumber(),
            (string) $order->getCurrencyCode(),
            $customer instanceof \Sylius\Component\Core\Model\CustomerInterface ? CustomerView::fromCustomer($customer) : null,
            null === $channel ? null : ChannelView::fromChannel($channel),
        );
    }
}
