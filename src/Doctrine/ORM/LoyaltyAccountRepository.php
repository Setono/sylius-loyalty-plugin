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

    public function findIdsWithLotsExpiringAtOrBefore(\DateTimeInterface $asOf): array
    {
        $ids = $this->getEntityManager()
            ->createQuery(sprintf(
                'SELECT DISTINCT IDENTITY(lot.account) FROM %s lot ' .
                'WHERE lot.expiresAt IS NOT NULL AND lot.expiresAt <= :asOf ' .
                'AND NOT EXISTS (SELECT expiration.id FROM %s expiration WHERE expiration.lot = lot)',
                CreditLoyaltyTransaction::class,
                ExpireLoyaltyTransaction::class,
            ))
            ->setParameter('asOf', $asOf)
            ->getSingleColumnResult()
        ;

        $accountIds = [];
        /** @var mixed $id */
        foreach ($ids as $id) {
            Assert::integerish($id);
            $accountIds[] = (int) $id;
        }

        return $accountIds;
    }
}
