<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider\Shop;

use Sylius\Component\Core\Model\OrderInterface;

interface CartRedemptionViewProviderInterface
{
    /**
     * Returns null when the widget must not render: anonymous carts, disabled accounts, or a
     * balance below the redemption minimum with no active redemption to manage.
     */
    public function getView(OrderInterface $cart): ?CartRedemptionView;
}
