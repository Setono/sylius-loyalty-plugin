<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Repository;

use Setono\SyliusLoyaltyPlugin\Model\EarnReferralLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\ReferralInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

class ReferralRepository extends EntityRepository implements ReferralRepositoryInterface
{
    public function findOneByRefereeAndChannel(CustomerInterface $referee, ChannelInterface $channel): ?ReferralInterface
    {
        $referral = $this->findOneBy(['refereeCustomer' => $referee, 'channel' => $channel]);
        \assert(null === $referral || $referral instanceof ReferralInterface);

        return $referral;
    }

    public function countRewardedSince(LoyaltyAccountInterface $referrerAccount, \DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.referrerAccount = :account')
            ->andWhere('r.status = :status')
            ->andWhere('r.qualifiedAt >= :since')
            ->setParameter('account', $referrerAccount)
            ->setParameter('status', ReferralInterface::STATUS_REWARDED)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findPendingOlderThan(\DateTimeImmutable $threshold, int $limit): array
    {
        /** @var list<ReferralInterface> $referrals */
        $referrals = $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->andWhere('r.createdAt < :threshold')
            ->setParameter('status', ReferralInterface::STATUS_PENDING)
            ->setParameter('threshold', $threshold)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        return $referrals;
    }

    public function findWithIpHashOlderThan(\DateTimeImmutable $threshold, int $limit): array
    {
        /** @var list<ReferralInterface> $referrals */
        $referrals = $this->createQueryBuilder('r')
            ->andWhere('r.registrationIpHash IS NOT NULL')
            ->andWhere('r.createdAt < :threshold')
            ->setParameter('threshold', $threshold)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        return $referrals;
    }

    public function getReferrerStats(LoyaltyAccountInterface $referrerAccount): array
    {
        $rewarded = (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.referrerAccount = :account')
            ->andWhere('r.status = :status')
            ->setParameter('account', $referrerAccount)
            ->setParameter('status', ReferralInterface::STATUS_REWARDED)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $pointsEarned = (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COALESCE(SUM(t.points), 0)')
            ->from(EarnReferralLoyaltyTransaction::class, 't')
            ->andWhere('t.account = :account')
            ->setParameter('account', $referrerAccount)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return ['rewarded' => $rewarded, 'pointsEarned' => $pointsEarned];
    }
}
