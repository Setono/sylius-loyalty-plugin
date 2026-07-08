<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule\Amount;

/**
 * `points` per `per` minor units of the eligible basis, e.g. 1 point per 100 (1.00). The division
 * floors — the fractional remainder never earns — which is the shop's default earning rate when used
 * on a base order-scoped rule (§3.8).
 */
final class PerAmount implements EarningAmountInterface
{
    public function getType(): string
    {
        return 'per_amount';
    }

    public function calculate(EarningAmountContext $context, array $configuration): int
    {
        $points = $configuration['points'] ?? 0;
        $points = is_numeric($points) ? (int) $points : 0;

        $per = $configuration['per'] ?? 0;
        $per = is_numeric($per) ? (int) $per : 0;

        if ($per <= 0) {
            return 0;
        }

        return max(0, intdiv($context->basis, $per) * $points);
    }
}
