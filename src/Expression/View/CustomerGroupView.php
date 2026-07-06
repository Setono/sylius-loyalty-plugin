<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression\View;

use Sylius\Component\Customer\Model\CustomerGroupInterface;

// todo: I don't want this extra layer of 'views'. These rules are made up by administrators, so let them do it on entities directly
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
