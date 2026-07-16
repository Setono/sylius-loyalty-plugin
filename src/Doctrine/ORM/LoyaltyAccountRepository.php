<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Doctrine\ORM;

use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\ExpireLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Webmozart\Assert\Assert;

class LoyaltyAccountRepository extends EntityRepository implements LoyaltyAccountRepositoryInterface
{
    public function findOneByCustomerAndChannel(CustomerInterface $customer, ChannelInterface $channel): ?LoyaltyAccountInterface
    {
        $account = $this->findOneBy(['customer' => $customer, 'channel' => $channel]);
        Assert::nullOrIsInstanceOf($account, LoyaltyAccountInterface::class);

        return $account;
    }

    public function findByCustomer(CustomerInterface $customer): array
    {
        $accounts = $this->findBy(['customer' => $customer]);
        Assert::allIsInstanceOf($accounts, LoyaltyAccountInterface::class);

        return array_values($accounts);
    }

    public function findWithLotsExpiringAtOrBefore(\DateTimeInterface $asOf): array
    {
        $accounts = $this->createQueryBuilder('account')
            ->andWhere(sprintf(
                'EXISTS (SELECT lot.id FROM %s lot WHERE lot.account = account ' .
                'AND lot.expiresAt IS NOT NULL AND lot.expiresAt <= :asOf ' .
                'AND NOT EXISTS (SELECT expiration.id FROM %s expiration WHERE expiration.lot = lot))',
                CreditLoyaltyTransaction::class,
                ExpireLoyaltyTransaction::class,
            ))
            ->setParameter('asOf', $asOf)
            ->getQuery()
            ->getResult()
        ;

        Assert::isArray($accounts);
        Assert::allIsInstanceOf($accounts, LoyaltyAccountInterface::class);

        return array_values($accounts);
    }

    public function countAll(): int
    {
        $count = $this->createQueryBuilder('account')
            ->select('COUNT(account.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        Assert::integerish($count);

        return (int) $count;
    }

    public function sumBalances(): int
    {
        $sum = $this->createQueryBuilder('account')
            ->select('COALESCE(SUM(account.balance), 0)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        Assert::integerish($sum);

        return (int) $sum;
    }
}
