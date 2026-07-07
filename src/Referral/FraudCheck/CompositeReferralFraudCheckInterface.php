<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Referral\FraudCheck;

use Setono\SyliusLoyaltyPlugin\Model\ReferralInterface;
use Sylius\Component\Core\Model\OrderInterface;

interface CompositeReferralFraudCheckInterface
{
    /**
     * @return list<FraudFlag>
     */
    public function checkAll(ReferralInterface $referral, OrderInterface $order): array;
}
