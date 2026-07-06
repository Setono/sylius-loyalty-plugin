<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Repository;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<LoyaltyProgramInterface>
 */
interface LoyaltyProgramRepositoryInterface extends RepositoryInterface
{
    public function findOneByChannel(ChannelInterface $channel): ?LoyaltyProgramInterface;
}
