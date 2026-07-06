<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Model\CodeAwareInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\ToggleableInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;

interface TierInterface extends ResourceInterface, CodeAwareInterface, ToggleableInterface, TranslatableInterface
{
    public function getId(): ?int;

    public function getChannel(): ?ChannelInterface;

    public function setChannel(?ChannelInterface $channel): void;

    public function getName(): ?string;

    public function setName(?string $name): void;

    /**
     * Tiers order by position within a channel; the highest position is the top tier.
     */
    public function getPosition(): int;

    public function setPosition(int $position): void;

    /**
     * The code of a registered qualification basis (points_earned, amount_spent,
     * orders_count, or a custom one).
     */
    public function getQualificationBasis(): string;

    public function setQualificationBasis(string $qualificationBasis): void;

    /**
     * The unit depends on the basis: points, minor currency units, or orders.
     */
    public function getThreshold(): int;

    public function setThreshold(int $threshold): void;

    public function getEarningMultiplier(): float;

    public function setEarningMultiplier(float $earningMultiplier): void;

    /**
     * Hex color for the tier badge in admin and shop.
     */
    public function getColor(): ?string;

    public function setColor(?string $color): void;

    public function getBenefitsDescription(): ?string;

    public function setBenefitsDescription(?string $benefitsDescription): void;
}
