<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression\View;

use Sylius\Component\Core\Model\CustomerInterface;

final class CustomerView
{
    private function __construct(
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?CustomerGroupView $group,
    ) {
    }

    public static function fromCustomer(CustomerInterface $customer): self
    {
        $group = $customer->getGroup();

        return new self(
            (string) $customer->getEmail(),
            (string) $customer->getFirstName(),
            (string) $customer->getLastName(),
            null === $group ? null : CustomerGroupView::fromCustomerGroup($group),
        );
    }
}
