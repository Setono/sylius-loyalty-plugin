<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Referral\FraudCheck;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\ReferralInterface;
use Setono\SyliusLoyaltyPlugin\Repository\ReferralRepositoryInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * Flags a referee registered from the same IP as another referee of the same referrer —
 * mass self-referrals from one machine. Opt-in (setono_sylius_loyalty.referral.registration_ip_check):
 * it alone requires the salted-hash storage and the 90-day purge.
 */
final class RegistrationIpCheck implements ReferralFraudCheckInterface
{
    public function __construct(
        private readonly ReferralRepositoryInterface $referralRepository,
        private readonly bool $enabled,
    ) {
    }

    public function check(ReferralInterface $referral, OrderInterface $order): ?FraudFlag
    {
        $hash = $referral->getRegistrationIpHash();
        if (!$this->enabled || null === $hash) {
            return null;
        }

        $referrerAccount = $referral->getReferrerAccount();
        if (!$referrerAccount instanceof LoyaltyAccountInterface) {
            return null;
        }

        /** @var list<ReferralInterface> $siblings */
        $siblings = $this->referralRepository->findBy(['referrerAccount' => $referrerAccount, 'registrationIpHash' => $hash]);
        foreach ($siblings as $sibling) {
            if ($sibling->getId() !== $referral->getId()) {
                return new FraudFlag('registration_ip', 'Another referee of the same referrer registered from the same IP');
            }
        }

        return null;
    }
}
