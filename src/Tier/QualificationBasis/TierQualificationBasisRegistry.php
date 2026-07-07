<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tier\QualificationBasis;

use Setono\CompositeCompilerPass\CompositeService;
use Setono\SyliusLoyaltyPlugin\Exception\InvalidTierQualificationBasisException;

/**
 * @extends CompositeService<TierQualificationBasisInterface>
 */
final class TierQualificationBasisRegistry extends CompositeService implements TierQualificationBasisRegistryInterface
{
    public function get(string $code): TierQualificationBasisInterface
    {
        $available = [];
        foreach ($this->services as $basis) {
            if ($basis->getCode() === $code) {
                return $basis;
            }
            $available[] = $basis->getCode();
        }

        throw InvalidTierQualificationBasisException::unknown($code, $available);
    }

    public function all(): array
    {
        return $this->services;
    }
}
