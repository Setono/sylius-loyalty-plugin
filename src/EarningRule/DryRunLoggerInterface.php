<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * Persists what dry-run rules would have awarded to the audit table (pruned after 30 days by
 * the setono:sylius-loyalty:prune-dry-run-results command).
 */
interface DryRunLoggerInterface
{
    public function log(
        EvaluationResult $result,
        ?LoyaltyAccountInterface $account = null,
        ?OrderInterface $order = null,
    ): void;
}
