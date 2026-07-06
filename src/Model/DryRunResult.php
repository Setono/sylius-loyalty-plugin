<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\OrderInterface;

class DryRunResult implements DryRunResultInterface
{
    protected ?int $id = null;

    protected ?EarningRuleInterface $rule = null;

    protected ?LoyaltyAccountInterface $account = null;

    protected ?OrderInterface $order = null;

    protected int $points = 0;

    /** @var array<string, mixed>|null */
    protected ?array $details = [];

    protected \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

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

    public function getAccount(): ?LoyaltyAccountInterface
    {
        return $this->account;
    }

    public function setAccount(?LoyaltyAccountInterface $account): void
    {
        $this->account = $account;
    }

    public function getOrder(): ?OrderInterface
    {
        return $this->order;
    }

    public function setOrder(?OrderInterface $order): void
    {
        $this->order = $order;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): void
    {
        $this->points = $points;
    }

    public function getDetails(): array
    {
        return $this->details ?? [];
    }

    public function setDetails(array $details): void
    {
        $this->details = $details;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
