<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Resource\Model\AbstractTranslation;

class TierTranslation extends AbstractTranslation implements TierTranslationInterface
{
    protected ?int $id = null;

    protected ?string $benefitsDescription = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBenefitsDescription(): ?string
    {
        return $this->benefitsDescription;
    }

    public function setBenefitsDescription(?string $benefitsDescription): void
    {
        $this->benefitsDescription = $benefitsDescription;
    }
}
