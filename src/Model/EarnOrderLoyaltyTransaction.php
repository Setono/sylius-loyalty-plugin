<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\OrderInterface;

class EarnOrderLoyaltyTransaction extends CreditLoyaltyTransaction implements EarnOrderLoyaltyTransactionInterface
{
    protected ?OrderInterface $order = null;

    /** @var array<int, mixed>|null */
    protected ?array $rulesBreakdown = null;

    protected ?int $basisAmount = null;

    public static function getType(): string
    {
        return 'earn_order';
    }

    public function getOrder(): ?OrderInterface
    {
        return $this->order;
    }

    public function setOrder(?OrderInterface $order): void
    {
        $this->order = $order;
    }

    public function getRulesBreakdown(): array
    {
        return $this->rulesBreakdown ?? [];
    }

    public function setRulesBreakdown(array $rulesBreakdown): void
    {
        $this->rulesBreakdown = $rulesBreakdown;
    }

    public function getBasisAmount(): int
    {
        return $this->basisAmount ?? 0;
    }

    public function setBasisAmount(int $basisAmount): void
    {
        $this->basisAmount = $basisAmount;
    }
}
