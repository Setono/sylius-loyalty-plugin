<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

interface EarningRuleConditionInterface extends ResourceInterface
{
    public function getId(): ?int;

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
