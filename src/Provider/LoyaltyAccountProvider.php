<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class LoyaltyAccountProvider implements LoyaltyAccountProviderInterface
{
    use ORMTrait;

    /**
     * @param FactoryInterface<LoyaltyAccountInterface> $factory
     */
    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly LoyaltyAccountRepositoryInterface $repository,
        ManagerRegistry $managerRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function getAccount(CustomerInterface $customer, ChannelInterface $channel): LoyaltyAccountInterface
    {
        $account = $this->repository->findOneByCustomerAndChannel($customer, $channel);
        if (null !== $account) {
            return $account;
        }

        $account = $this->factory->createNew();
        $account->setCustomer($customer);
        $account->setChannel($channel);

        $manager = $this->getManager($account);
        $manager->persist($account);
        $manager->flush();

        return $account;
    }
}
