<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Model\ToggleableTrait;
use Sylius\Component\Resource\Model\TranslationInterface;
use Sylius\Resource\Model\TranslatableTrait;

class Tier implements TierInterface
{
    use ToggleableTrait;
    use TranslatableTrait {
        __construct as private initializeTranslationsCollection;
    }

    protected ?int $id = null;

    protected ?string $code = null;

    protected ?ChannelInterface $channel = null;

    protected ?string $name = null;

    protected int $position = 0;

    protected string $qualificationBasis = 'points_earned';

    protected int $threshold = 0;

    protected float $earningMultiplier = 1.0;

    protected ?string $color = null;

    public function __construct()
    {
        $this->initializeTranslationsCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(?ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getQualificationBasis(): string
    {
        return $this->qualificationBasis;
    }

    public function setQualificationBasis(string $qualificationBasis): void
    {
        $this->qualificationBasis = $qualificationBasis;
    }

    public function getThreshold(): int
    {
        return $this->threshold;
    }

    public function setThreshold(int $threshold): void
    {
        $this->threshold = $threshold;
    }

    public function getEarningMultiplier(): float
    {
        return $this->earningMultiplier;
    }

    public function setEarningMultiplier(float $earningMultiplier): void
    {
        $this->earningMultiplier = $earningMultiplier;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): void
    {
        $this->color = $color;
    }

    public function getBenefitsDescription(): ?string
    {
        $translation = $this->getTranslation();
        \assert($translation instanceof TierTranslationInterface);

        return $translation->getBenefitsDescription();
    }

    public function setBenefitsDescription(?string $benefitsDescription): void
    {
        $translation = $this->getTranslation();
        \assert($translation instanceof TierTranslationInterface);

        $translation->setBenefitsDescription($benefitsDescription);
    }

    protected function createTranslation(): TranslationInterface
    {
        return new TierTranslation();
    }
}
