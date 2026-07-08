<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Doctrine\ORM;

use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Setono\SyliusLoyaltyPlugin\Repository\EarningRuleRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\ChannelInterface;
use Webmozart\Assert\Assert;

class EarningRuleRepository extends EntityRepository implements EarningRuleRepositoryInterface
{
    public function findEnabledByChannelAndTrigger(ChannelInterface $channel, string $trigger): array
    {
        $rules = $this->createQueryBuilder('o')
            ->andWhere('o.channel = :channel')
            ->andWhere('o.trigger = :trigger')
            ->andWhere('o.enabled = true')
            ->setParameter('channel', $channel)
            ->setParameter('trigger', $trigger)
            ->addOrderBy('o.priority', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        Assert::isArray($rules);
        Assert::allIsInstanceOf($rules, EarningRuleInterface::class);

        return array_values($rules);
    }
}
