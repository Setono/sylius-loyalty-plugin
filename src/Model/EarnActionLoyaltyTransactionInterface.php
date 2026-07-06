<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

/**
 * Points earned from an action trigger (registration, review, birthday, custom triggers).
 * The source identifier deduplicates awards: at most one entry per (account, source identifier),
 * enforced by a database unique constraint.
 */
interface EarnActionLoyaltyTransactionInterface extends CreditLoyaltyTransactionInterface, RulesBreakdownAwareInterface
{
    public function getSourceIdentifier(): ?string;

    public function setSourceIdentifier(?string $sourceIdentifier): void;
}
