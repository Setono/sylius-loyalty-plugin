<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

class EarningRuleCondition implements EarningRuleConditionInterface
{
    protected ?int $id = null;

    protected ?EarningRuleInterface $rule = null;

    protected ?string $type = null;

    /** @var array<string, mixed> */
    protected array $configuration = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRule(): ?EarningRuleInterface
    {
        return $this->rule;
    }

    public function setRule(?EarningRuleInterface $rule): void
    {
        $this->rule = $rule;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }
}
