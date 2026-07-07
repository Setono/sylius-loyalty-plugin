<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class LoyaltyAccountProvider implements LoyaltyAccountProviderInterface
{
    /**
     * @param FactoryInterface<LoyaltyAccountInterface> $accountFactory
     */
    public function __construct(
        private readonly LoyaltyAccountRepositoryInterface $accountRepository,
        private readonly FactoryInterface $accountFactory,
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    public function getByCustomerAndChannel(CustomerInterface $customer, ChannelInterface $channel): LoyaltyAccountInterface
    {
        $account = $this->accountRepository->findOneByCustomerAndChannel($customer, $channel);
        if (null !== $account) {
            return $account;
        }

        $account = $this->accountFactory->createNew();
        Assert::isInstanceOf($account, LoyaltyAccountInterface::class);
        $account->setCustomer($customer);
        $account->setChannel($channel);

        $manager = $this->managerRegistry->getManagerForClass($account::class);
        Assert::notNull($manager);

        try {
            $manager->persist($account);
            $manager->flush();
        } catch (UniqueConstraintViolationException) {
            // Another process created the account concurrently. The entity manager is closed
            // by the failed flush, so reset it and load the winning row.
            $this->managerRegistry->resetManager();

            $account = $this->accountRepository->findOneByCustomerAndChannel($customer, $channel);
            Assert::notNull($account);
        }

        return $account;
    }
}
