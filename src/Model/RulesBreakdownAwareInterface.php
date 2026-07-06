<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

/**
 * Implemented by earn transactions produced by the rule engine, recording how the awarded
 * points were computed: which rules contributed how many points, and which multipliers applied.
 */
interface RulesBreakdownAwareInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getRulesBreakdown(): array;

    /**
     * @param array<string, mixed> $rulesBreakdown
     */
    public function setRulesBreakdown(array $rulesBreakdown): void;
}
