<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression\View;

use Sylius\Component\Customer\Model\CustomerGroupInterface;

/**
 * Expressions evaluate against these views, never against entities: ExpressionLanguage's dot
 * syntax reads public properties, and exposing only curated views makes the sandbox whitelist
 * physically enforced at evaluation time.
 */
final class CustomerGroupView
{
    private function __construct(
        public readonly string $code,
        public readonly string $name,
    ) {
    }

    public static function fromCustomerGroup(CustomerGroupInterface $group): self
    {
        return new self(
            (string) $group->getCode(),
            (string) $group->getName(),
        );
    }
}
