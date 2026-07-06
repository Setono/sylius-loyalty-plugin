<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TranslationInterface;

interface TierTranslationInterface extends ResourceInterface, TranslationInterface
{
    public function getId(): ?int;

    public function getBenefitsDescription(): ?string;

    public function setBenefitsDescription(?string $benefitsDescription): void;
}
