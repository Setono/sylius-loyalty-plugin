<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

abstract class LoyaltyTransaction implements LoyaltyTransactionInterface
{
    protected ?int $id = null;

    protected ?LoyaltyAccountInterface $account = null;

    protected int $points = 0;

    protected \DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable();
    }

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

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(\DateTimeImmutable $occurredAt): void
    {
        $this->occurredAt = $occurredAt;
    }

    /**
     * The Doctrine discriminator value of this transaction type. Plugin-shipped types are
     * declared in the XML mapping; custom types registered as Sylius resources are added to
     * the discriminator map from this value.
     */
    abstract public static function getDiscriminator(): string;
}
