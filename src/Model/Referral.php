<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

class Referral implements ReferralInterface
{
    protected ?int $id = null;

    protected ?LoyaltyAccountInterface $referrerAccount = null;

    protected ?CustomerInterface $refereeCustomer = null;

    protected ?ChannelInterface $channel = null;

    protected ?string $code = null;

    protected string $status = self::STATUS_PENDING;

    protected ?OrderInterface $refereeFirstOrder = null;

    protected ?\DateTimeImmutable $createdAt = null;

    protected ?\DateTimeImmutable $qualifiedAt = null;

    /** @var list<array{check: string, detail: string|null}>|null */
    protected ?array $fraudFlags = null;

    protected ?string $registrationIpHash = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReferrerAccount(): ?LoyaltyAccountInterface
    {
        return $this->referrerAccount;
    }

    public function setReferrerAccount(?LoyaltyAccountInterface $referrerAccount): void
    {
        $this->referrerAccount = $referrerAccount;
    }

    public function getRefereeCustomer(): ?CustomerInterface
    {
        return $this->refereeCustomer;
    }

    public function setRefereeCustomer(?CustomerInterface $refereeCustomer): void
    {
        $this->refereeCustomer = $refereeCustomer;
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(?ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getRefereeFirstOrder(): ?OrderInterface
    {
        return $this->refereeFirstOrder;
    }

    public function setRefereeFirstOrder(?OrderInterface $refereeFirstOrder): void
    {
        $this->refereeFirstOrder = $refereeFirstOrder;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getQualifiedAt(): ?\DateTimeImmutable
    {
        return $this->qualifiedAt;
    }

    public function setQualifiedAt(?\DateTimeImmutable $qualifiedAt): void
    {
        $this->qualifiedAt = $qualifiedAt;
    }

    public function getFraudFlags(): array
    {
        return $this->fraudFlags ?? [];
    }

    public function setFraudFlags(array $fraudFlags): void
    {
        $this->fraudFlags = $fraudFlags;
    }

    public function getRegistrationIpHash(): ?string
    {
        return $this->registrationIpHash;
    }

    public function setRegistrationIpHash(?string $registrationIpHash): void
    {
        $this->registrationIpHash = $registrationIpHash;
    }
}
