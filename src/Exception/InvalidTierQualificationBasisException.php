<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Exception;

final class InvalidTierQualificationBasisException extends \InvalidArgumentException implements ExceptionInterface
{
    /**
     * @param list<string> $available
     */
    public static function unknown(string $code, array $available): self
    {
        return new self(sprintf(
            'No tier qualification basis registered with code "%s" (available: %s)',
            $code,
            implode(', ', $available),
        ));
    }
}
