<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Resource\Model\TimestampableTrait;
use Sylius\Component\Resource\Model\ToggleableTrait;

class LoyaltyAccount implements LoyaltyAccountInterface
{
    use TimestampableTrait;
    use ToggleableTrait;

    protected ?int $id = null;

    protected ?CustomerInterface $customer = null;

    protected ?ChannelInterface $channel = null;

    protected int $balance = 0;

    protected int $lifetimeEarned = 0;

    protected ?TierInterface $tier = null;

    /**
     * When the account first evaluated below its current tier's threshold — drives the
     * downgrade grace period. Cleared whenever the account re-qualifies.
     */
    protected ?\DateTimeImmutable $tierBelowThresholdSince = null;

    protected ?string $referralCode = null;

    protected ?string $anonymizedToken = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

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

    public function getTier(): ?TierInterface
    {
        return $this->tier;
    }

    public function setTier(?TierInterface $tier): void
    {
        $this->tier = $tier;
    }

    public function getTierBelowThresholdSince(): ?\DateTimeImmutable
    {
        return $this->tierBelowThresholdSince;
    }

    public function setTierBelowThresholdSince(?\DateTimeImmutable $tierBelowThresholdSince): void
    {
        $this->tierBelowThresholdSince = $tierBelowThresholdSince;
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

    public function getAnonymizedToken(): ?string
    {
        return $this->anonymizedToken;
    }

    public function setAnonymizedToken(?string $anonymizedToken): void
    {
        $this->anonymizedToken = $anonymizedToken;
    }
}
