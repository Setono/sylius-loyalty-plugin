<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider\Shop;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;

interface TierProgressProviderInterface
{
    /**
     * Null when the channel has no enabled tiers.
     */
    public function getProgress(LoyaltyAccountInterface $account): ?TierProgress;
}
