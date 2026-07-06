<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider\Shop;

use Setono\SyliusLoyaltyPlugin\Model\TierInterface;

final class TierProgress
{
    public function __construct(
        public readonly ?TierInterface $current,
        public readonly ?TierInterface $next,
        public readonly int $metric,
        public readonly int $threshold,
        public readonly bool $topTier,
    ) {
    }

    public function getPercent(): int
    {
        if ($this->threshold <= 0) {
            return 0;
        }

        return (int) min(100, floor($this->metric / $this->threshold * 100));
    }

    public function getRemaining(): int
    {
        return max(0, $this->threshold - $this->metric);
    }
}
