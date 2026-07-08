<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Message\Command;

use Sylius\Component\Core\Model\OrderInterface;
use Webmozart\Assert\Assert;

/**
 * Reverses the loyalty points an order earned, because it was cancelled or refunded. Accepts either the
 * order or its id and stores only the id, so it is safe to handle asynchronously; the handler loads a
 * fresh order and delegates to the (idempotent) ledger clawback. Dispatched by the state-machine
 * trigger on cancellation / refund, or by application code.
 */
final class ClawbackOrderPoints
{
    public readonly int $order;

    public function __construct(int|OrderInterface $order)
    {
        if ($order instanceof OrderInterface) {
            $id = $order->getId();
            Assert::integer($id, 'The order must be persisted before its points can be clawed back.');
            $order = $id;
        }

        $this->order = $order;
    }
}
