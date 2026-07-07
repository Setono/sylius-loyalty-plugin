<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * One condition row of an earning rule: a condition type (a tagged service) and its
 * configuration. Expression-mode conditions are rows of type "expression" whose configuration
 * holds the expression.
 */
interface EarningRuleConditionInterface extends ResourceInterface
{
    public function getRule(): ?EarningRuleInterface;

    public function setRule(?EarningRuleInterface $rule): void;

    public function getType(): ?string;

    public function setType(?string $type): void;

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array;

    /**
     * @param array<string, mixed> $configuration
     */
    public function setConfiguration(array $configuration): void;
}
