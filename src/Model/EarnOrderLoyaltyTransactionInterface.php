<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\OrderInterface;

interface EarnOrderLoyaltyTransactionInterface extends CreditLoyaltyTransactionInterface
{
    public function getOrder(): ?OrderInterface;

    public function setOrder(?OrderInterface $order): void;

    /**
     * @return array<int, mixed> rule id => points contributed (with multipliers noted)
     */
    public function getRulesBreakdown(): array;

    /**
     * @param array<int, mixed> $rulesBreakdown
     */
    public function setRulesBreakdown(array $rulesBreakdown): void;

    public function getBasisAmount(): int;

    public function setBasisAmount(int $basisAmount): void;
}
