<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tier\QualificationBasis;

interface TierQualificationBasisRegistryInterface
{
    /**
     * @throws \Setono\SyliusLoyaltyPlugin\Exception\InvalidTierQualificationBasisException if the code is not registered
     */
    public function get(string $code): TierQualificationBasisInterface;

    /**
     * @return list<TierQualificationBasisInterface>
     */
    public function all(): array;
}
