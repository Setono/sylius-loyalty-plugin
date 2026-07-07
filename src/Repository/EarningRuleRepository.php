<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Repository;

use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;

class EarningRuleRepository extends EntityRepository implements EarningRuleRepositoryInterface
{
    public function findForEvaluation(ChannelInterface $channel, string $trigger): array
    {
        /** @var list<EarningRuleInterface> $rules */
        $rules = $this->createQueryBuilder('r')
            ->andWhere('r.channel = :channel')
            ->andWhere('r.trigger = :trigger')
            ->andWhere('r.enabled = true')
            ->setParameter('channel', $channel)
            ->setParameter('trigger', $trigger)
            ->orderBy('r.priority', 'DESC')
            ->addOrderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $rules;
    }
}
