<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Sylius\Component\Resource\Model\ToggleableInterface;

/**
 * One loyalty account per (customer, channel).
 *
 * The balance and lifetimeEarned are cached values derived from the append-only ledger; they are
 * never hand-edited outside the ledger write path. The account is created lazily the first time the
 * customer earns or needs points in a channel.
 */
interface LoyaltyAccountInterface extends ResourceInterface, ToggleableInterface, TimestampableInterface
{
    public function getId(): ?int;

    public function getCustomer(): ?CustomerInterface;

    public function setCustomer(?CustomerInterface $customer): void;

    public function getChannel(): ?ChannelInterface;

    public function setChannel(?ChannelInterface $channel): void;

    public function getBalance(): int;

    public function setBalance(int $balance): void;

    public function getLifetimeEarned(): int;

    public function setLifetimeEarned(int $lifetimeEarned): void;

    public function getReferralCode(): ?string;

    public function setReferralCode(?string $referralCode): void;
}
