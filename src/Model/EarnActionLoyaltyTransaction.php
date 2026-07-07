<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

class EarnActionLoyaltyTransaction extends CreditLoyaltyTransaction implements EarnActionLoyaltyTransactionInterface
{
    protected ?string $sourceIdentifier = null;

    /** @var array<int, mixed>|null */
    protected ?array $rulesBreakdown = null;

    public static function getType(): string
    {
        return 'earn_action';
    }

    public function getSourceIdentifier(): ?string
    {
        return $this->sourceIdentifier;
    }

    public function setSourceIdentifier(?string $sourceIdentifier): void
    {
        $this->sourceIdentifier = $sourceIdentifier;
    }

    public function getRulesBreakdown(): array
    {
        return $this->rulesBreakdown ?? [];
    }

    public function setRulesBreakdown(array $rulesBreakdown): void
    {
        $this->rulesBreakdown = $rulesBreakdown;
    }
}
