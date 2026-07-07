<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

abstract class LoyaltyTransaction implements LoyaltyTransactionInterface
{
    protected ?int $id = null;

    protected ?LoyaltyAccountInterface $account = null;

    protected int $points = 0;

    protected ?\DateTimeInterface $occurredAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccount(): ?LoyaltyAccountInterface
    {
        return $this->account;
    }

    public function setAccount(?LoyaltyAccountInterface $account): void
    {
        $this->account = $account;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): void
    {
        $this->points = $points;
    }

    public function getOccurredAt(): ?\DateTimeInterface
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(?\DateTimeInterface $occurredAt): void
    {
        $this->occurredAt = $occurredAt;
    }
}
