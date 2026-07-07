<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule;

use Setono\SyliusLoyaltyPlugin\Event\Trigger\EarningTriggerEvent;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransactionInterface;

/**
 * Evaluates a trigger event's earning rules and writes the award. Synchronous by design:
 * typed event objects don't belong on a queue and the work is light. Only the idempotency
 * (unique constraint) violation is a silent no-op; any other failure bubbles into the
 * dispatching action — silently losing awards is worse than a loud error.
 */
interface ActionTriggerProcessorInterface
{
    public function process(EarningTriggerEvent $event): ?EarnActionLoyaltyTransactionInterface;
}
