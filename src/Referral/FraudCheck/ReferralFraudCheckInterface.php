<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Referral\FraudCheck;

use Setono\SyliusLoyaltyPlugin\Model\ReferralInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * A referral fraud check, run at qualification against the qualifying order. Implementations
 * are tagged setono_sylius_loyalty.referral_fraud_check (autoconfigured); any returned flag
 * rejects the referral (admins can override).
 */
interface ReferralFraudCheckInterface
{
    public function check(ReferralInterface $referral, OrderInterface $order): ?FraudFlag;
}
