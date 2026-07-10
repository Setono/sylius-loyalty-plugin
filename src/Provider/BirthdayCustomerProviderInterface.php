<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider;

use Sylius\Component\Core\Model\CustomerInterface;

interface BirthdayCustomerProviderInterface
{
    /**
     * The customers whose birthday falls on the given date's month and day (any year).
     *
     * @return iterable<CustomerInterface>
     */
    public function getCustomersWithBirthday(\DateTimeInterface $date): iterable;
}
