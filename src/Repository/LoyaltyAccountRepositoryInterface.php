<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Repository;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<LoyaltyAccountInterface>
 */
interface LoyaltyAccountRepositoryInterface extends RepositoryInterface
{
    public function findOneByCustomerAndChannel(CustomerInterface $customer, ChannelInterface $channel): ?LoyaltyAccountInterface;

    /**
     * @return list<LoyaltyAccountInterface>
     */
    public function findByCustomer(CustomerInterface $customer): array;

    /**
     * The accounts holding at least one lot that has expired on or before $asOf and does not yet have an
     * expire row — i.e. the accounts the expire-points command still needs to process.
     *
     * @return list<LoyaltyAccountInterface>
     */
    public function findWithLotsExpiringAtOrBefore(\DateTimeInterface $asOf): array;
}
