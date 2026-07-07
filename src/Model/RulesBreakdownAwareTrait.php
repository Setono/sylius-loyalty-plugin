<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

trait RulesBreakdownAwareTrait
{
    /**
     * Nullable because the database column is nullable (it is shared with other transaction
     * types in the single-table inheritance table), but consumers always see an array.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $rulesBreakdown = [];

    /**
     * @return array<string, mixed>
     */
    public function getRulesBreakdown(): array
    {
        return $this->rulesBreakdown ?? [];
    }

    /**
     * @param array<string, mixed> $rulesBreakdown
     */
    public function setRulesBreakdown(array $rulesBreakdown): void
    {
        $this->rulesBreakdown = $rulesBreakdown;
    }
}
