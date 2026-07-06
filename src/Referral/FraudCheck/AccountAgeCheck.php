<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Referral\FraudCheck;

use Setono\SyliusLoyaltyPlugin\Model\ReferralInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * Flags referees whose customer account existed before the referral code was captured —
 * attribution should only ever happen at registration, so an older account signals cookie
 * manipulation.
 */
final class AccountAgeCheck implements ReferralFraudCheckInterface
{
    public function check(ReferralInterface $referral, OrderInterface $order): ?FraudFlag
    {
        $referee = $referral->getRefereeCustomer();
        $capturedAt = $referral->getCreatedAt();
        if (!$referee instanceof CustomerInterface || null === $capturedAt || null === $referee->getCreatedAt()) {
            return null;
        }

        // A small tolerance: registration and referral creation happen in the same request
        $registeredAt = \DateTimeImmutable::createFromInterface($referee->getCreatedAt());
        if ($registeredAt < $capturedAt->modify('-5 minutes')) {
            return new FraudFlag('account_age', 'The referee account existed before the referral was captured');
        }

        return null;
    }
}
