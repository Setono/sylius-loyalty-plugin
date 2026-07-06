<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tier;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Tier\QualificationBasis\DateRange;

final class EvaluationWindowResolver implements EvaluationWindowResolverInterface
{
    public function resolve(LoyaltyProgramInterface $program): ?DateRange
    {
        $now = new \DateTimeImmutable();

        return match ($program->getTierEvaluationWindow()) {
            LoyaltyProgramInterface::TIER_EVALUATION_WINDOW_LIFETIME => null,
            LoyaltyProgramInterface::TIER_EVALUATION_WINDOW_CALENDAR_YEAR => new DateRange(
                $now->modify('first day of january this year')->setTime(0, 0),
                $now->modify('first day of january next year')->setTime(0, 0),
            ),
            default => new DateRange($now->modify('-12 months'), $now),
        };
    }
}
