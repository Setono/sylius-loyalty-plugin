<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

interface EarnActionLoyaltyTransactionInterface extends CreditLoyaltyTransactionInterface
{
    public function getSourceIdentifier(): ?string;

    public function setSourceIdentifier(?string $sourceIdentifier): void;

    /**
     * @return array<int, mixed> rule id => points contributed (with multipliers noted)
     */
    public function getRulesBreakdown(): array;

    /**
     * @param array<int, mixed> $rulesBreakdown
     */
    public function setRulesBreakdown(array $rulesBreakdown): void;
}
