<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Message\Command;

use Sylius\Component\Core\Model\OrderInterface;
use Webmozart\Assert\Assert;

/**
 * Awards the loyalty points an order has earned. Accepts either the order or its id and stores only
 * the id, so it is safe to handle asynchronously; the handler loads a fresh order and delegates to the
 * OrderPointsAwarder, which is idempotent. Dispatched by the state-machine trigger at the program's
 * award moment, or by application code.
 */
final class AwardOrderPoints
{
    public readonly int $order;

    public function __construct(int|OrderInterface $order)
    {
        if ($order instanceof OrderInterface) {
            $id = $order->getId();
            Assert::integer($id, 'The order must be persisted before its points can be awarded.');
            $order = $id;
        }

        $this->order = $order;
    }
}
