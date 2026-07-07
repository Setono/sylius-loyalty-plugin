<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Repository;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;
use Webmozart\Assert\Assert;

class LoyaltyProgramRepository extends EntityRepository implements LoyaltyProgramRepositoryInterface
{
    public function findOneByChannel(ChannelInterface $channel): ?LoyaltyProgramInterface
    {
        $program = $this->findOneBy([
            'channel' => $channel,
        ]);

        Assert::nullOrIsInstanceOf($program, LoyaltyProgramInterface::class);

        return $program;
    }
}
