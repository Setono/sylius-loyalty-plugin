<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

class EarnActionLoyaltyTransaction extends CreditLoyaltyTransaction implements EarnActionLoyaltyTransactionInterface
{
    use RulesBreakdownAwareTrait;

    protected ?string $sourceIdentifier = null;

    public function getSourceIdentifier(): ?string
    {
        return $this->sourceIdentifier;
    }

    public function setSourceIdentifier(?string $sourceIdentifier): void
    {
        $this->sourceIdentifier = $sourceIdentifier;
    }
}
