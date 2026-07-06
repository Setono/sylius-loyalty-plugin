<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider\Shop;

/**
 * Everything the cart redemption widget renders.
 */
final class CartRedemptionView
{
    /**
     * @param list<array{points: int, amount: int}> $presets preset step buttons: points and
     *        the resulting discount in minor units (the only place a currency equivalent of
     *        points is shown)
     */
    public function __construct(
        public readonly int $balance,
        public readonly int $minRedeemPoints,
        public readonly array $presets,
        public readonly int $requestedPoints,
        public readonly int $appliedPoints,
        public readonly int $appliedAmount,
    ) {
    }

    public function canRedeem(): bool
    {
        return $this->balance >= $this->minRedeemPoints;
    }

    public function isClamped(): bool
    {
        return $this->appliedPoints > 0 && $this->appliedPoints < $this->requestedPoints;
    }
}
