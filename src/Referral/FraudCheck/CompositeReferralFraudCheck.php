<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Referral\FraudCheck;

use Setono\CompositeCompilerPass\CompositeService;
use Setono\SyliusLoyaltyPlugin\Model\ReferralInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * @extends CompositeService<ReferralFraudCheckInterface>
 */
final class CompositeReferralFraudCheck extends CompositeService implements CompositeReferralFraudCheckInterface
{
    public function checkAll(ReferralInterface $referral, OrderInterface $order): array
    {
        $flags = [];
        foreach ($this->services as $check) {
            $flag = $check->check($referral, $order);
            if (null !== $flag) {
                $flags[] = $flag;
            }
        }

        return $flags;
    }
}
