<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EventListener;

use Setono\SyliusLoyaltyPlugin\EarningRule\ActionTriggerProcessorInterface;
use Setono\SyliusLoyaltyPlugin\Event\Trigger\EarningTriggerEvent;

/**
 * Receives every registered trigger event (the compiler pass tags this listener once per
 * concrete event class) and delegates to the processor.
 */
final class EarningTriggerListener
{
    public function __construct(
        private readonly ActionTriggerProcessorInterface $processor,
    ) {
    }

    public function __invoke(EarningTriggerEvent $event): void
    {
        $this->processor->process($event);
    }
}
