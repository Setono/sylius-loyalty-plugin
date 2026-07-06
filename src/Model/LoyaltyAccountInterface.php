<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Channel\Model\ChannelAwareInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Sylius\Component\Resource\Model\ToggleableInterface;

interface LoyaltyAccountInterface extends
    ResourceInterface,
    TimestampableInterface,
    ToggleableInterface,
    ChannelAwareInterface
{
    public function getCustomer(): ?CustomerInterface;

    public function setCustomer(?CustomerInterface $customer): void;

    /**
     * The cached point balance, derived from the ledger. Only the ledger may update it.
     */
    public function getBalance(): int;

    public function setBalance(int $balance): void;

    /**
     * The cached sum of every credit except redemption rollbacks (restored points were
     * already counted when earned). Only the ledger may update it.
     */
    public function getLifetimeEarned(): int;

    public function setLifetimeEarned(int $lifetimeEarned): void;

    public function getReferralCode(): ?string;

    public function setReferralCode(?string $referralCode): void;
}
