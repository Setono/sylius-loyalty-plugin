<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Sylius\Component\Core\Model\CustomerInterface;
use Webmozart\Assert\Assert;

/**
 * Streams customers whose birthday matches the given month/day. The match is done in PHP (rather than a
 * DB-specific MONTH()/DAY() query) so it stays portable; the query is streamed, but a store with very
 * many customers with birthdays may want a DB-specific index/query instead.
 */
final class BirthdayCustomerProvider implements BirthdayCustomerProviderInterface
{
    use ORMTrait;

    /**
     * @param class-string<CustomerInterface> $customerClass
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly string $customerClass,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function getCustomersWithBirthday(\DateTimeInterface $date): iterable
    {
        $month = (int) $date->format('n');
        $day = (int) $date->format('j');

        $query = $this->getRepository($this->customerClass)
            ->createQueryBuilder('c')
            ->andWhere('c.birthday IS NOT NULL')
            ->getQuery()
        ;

        foreach ($query->toIterable() as $customer) {
            Assert::isInstanceOf($customer, CustomerInterface::class);

            $birthday = $customer->getBirthday();
            if (null !== $birthday && (int) $birthday->format('n') === $month && (int) $birthday->format('j') === $day) {
                yield $customer;
            }
        }
    }
}
