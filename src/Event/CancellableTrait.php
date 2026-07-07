<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

/**
 * @internal
 */
trait CancellableTrait
{
    private bool $cancelled = false;

    /**
     * Cancels the pending ledger write. No entry will be written.
     */
    public function cancel(): void
    {
        $this->cancelled = true;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled;
    }
}
