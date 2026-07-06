<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider\Admin;

interface DashboardStatsProviderInterface
{
    /**
     * @return array{accounts: int, earnedLast30Days: int, redeemedLast30Days: int}
     */
    public function getStats(): array;
}
