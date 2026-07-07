<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tier\QualificationBasis;

final class DateRange
{
    public function __construct(
        public readonly \DateTimeImmutable $start,
        public readonly \DateTimeImmutable $end,
    ) {
    }
}
