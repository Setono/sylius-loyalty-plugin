<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Doctrine\ORM;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Webmozart\Assert\Assert;

class LoyaltyAccountRepository extends EntityRepository implements LoyaltyAccountRepositoryInterface
{
    public function findOneByCustomerAndChannel(CustomerInterface $customer, ChannelInterface $channel): ?LoyaltyAccountInterface
    {
        $account = $this->findOneBy([
            'customer' => $customer,
            'channel' => $channel,
        ]);

        Assert::nullOrIsInstanceOf($account, LoyaltyAccountInterface::class);

        return $account;
    }
}
