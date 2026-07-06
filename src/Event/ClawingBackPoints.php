<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * Dispatched before a clawback debit is written. Listeners may adjust the points or cancel
 * the write entirely.
 */
final class ClawingBackPoints
{
    use CancellableTrait;

    public function __construct(
        private readonly LoyaltyAccountInterface $account,
        private int $points,
        private readonly OrderInterface $order,
        private readonly CreditLoyaltyTransactionInterface $earn,
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

    public function setPoints(int $points): void
    {
        $this->points = $points;
    }

    public function getOrder(): OrderInterface
    {
        return $this->order;
    }

    public function getEarn(): CreditLoyaltyTransactionInterface
    {
        return $this->earn;
    }
}
