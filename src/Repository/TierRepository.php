<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Repository;

use Setono\SyliusLoyaltyPlugin\Model\TierInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;

class TierRepository extends EntityRepository implements TierRepositoryInterface
{
    public function findQualifiable(ChannelInterface $channel): array
    {
        /** @var list<TierInterface> $tiers */
        $tiers = $this->createQueryBuilder('t')
            ->andWhere('t.channel = :channel')
            ->andWhere('t.enabled = true')
            ->setParameter('channel', $channel)
            ->orderBy('t.position', 'DESC')
            ->addOrderBy('t.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $tiers;
    }
}
