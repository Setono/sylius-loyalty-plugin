<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface ReferralInterface extends ResourceInterface
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_QUALIFIED = 'qualified';

    public const STATUS_REWARDED = 'rewarded';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public function getId(): ?int;

    public function getReferrerAccount(): ?LoyaltyAccountInterface;

    public function setReferrerAccount(?LoyaltyAccountInterface $referrerAccount): void;

    public function getRefereeCustomer(): ?CustomerInterface;

    public function setRefereeCustomer(?CustomerInterface $refereeCustomer): void;

    public function getChannel(): ?ChannelInterface;

    public function setChannel(?ChannelInterface $channel): void;

    /**
     * The referral code that was used.
     */
    public function getCode(): ?string;

    public function setCode(?string $code): void;

    public function getStatus(): string;

    public function setStatus(string $status): void;

    public function getRefereeFirstOrder(): ?OrderInterface;

    public function setRefereeFirstOrder(?OrderInterface $refereeFirstOrder): void;

    public function getCreatedAt(): ?\DateTimeImmutable;

    public function setCreatedAt(?\DateTimeImmutable $createdAt): void;

    public function getQualifiedAt(): ?\DateTimeImmutable;

    public function setQualifiedAt(?\DateTimeImmutable $qualifiedAt): void;

    /**
     * @return list<array{check: string, detail: string|null}>
     */
    public function getFraudFlags(): array;

    /**
     * @param list<array{check: string, detail: string|null}> $fraudFlags
     */
    public function setFraudFlags(array $fraudFlags): void;

    /**
     * The salted hash of the registration IP — present only when the opt-in IP fraud check is
     * enabled; purged after 90 days.
     */
    public function getRegistrationIpHash(): ?string;

    public function setRegistrationIpHash(?string $registrationIpHash): void;
}
