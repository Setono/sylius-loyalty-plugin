<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * Dispatched before the redemption debit is written at checkout completion. The points are
 * deliberately NOT adjustable — the debit must equal the applied points backing the order's
 * redemption adjustment. Cancelling aborts checkout completion with a validation error.
 */
final class RedeemingPoints
{
    use CancellableTrait;

    public function __construct(
        private readonly LoyaltyAccountInterface $account,
        private readonly int $points,
        private readonly OrderInterface $order,
    ) {
    }

    public function getAccount(): LoyaltyAccountInterface
    {
        return $this->account;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function getOrder(): OrderInterface
    {
        return $this->order;
    }
}
