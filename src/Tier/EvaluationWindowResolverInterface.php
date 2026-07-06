<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tier;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Tier\QualificationBasis\DateRange;

interface EvaluationWindowResolverInterface
{
    /**
     * The date range tiers qualify within; null means lifetime.
     */
    public function resolve(LoyaltyProgramInterface $program): ?DateRange;
}
