<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Resource\Model\TimestampableTrait;
use Sylius\Component\Resource\Model\ToggleableTrait;

class LoyaltyAccount implements LoyaltyAccountInterface
{
    use ToggleableTrait;
    use TimestampableTrait;

    protected ?int $id = null;

    protected ?CustomerInterface $customer = null;

    protected ?ChannelInterface $channel = null;

    protected int $balance = 0;

    protected int $lifetimeEarned = 0;

    protected ?string $referralCode = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?CustomerInterface
    {
        return $this->customer;
    }

    public function setCustomer(?CustomerInterface $customer): void
    {
        $this->customer = $customer;
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(?ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    public function setBalance(int $balance): void
    {
        $this->balance = $balance;
    }

    public function getLifetimeEarned(): int
    {
        return $this->lifetimeEarned;
    }

    public function setLifetimeEarned(int $lifetimeEarned): void
    {
        $this->lifetimeEarned = $lifetimeEarned;
    }

    public function getReferralCode(): ?string
    {
        return $this->referralCode;
    }

    public function setReferralCode(?string $referralCode): void
    {
        $this->referralCode = $referralCode;
    }
}
