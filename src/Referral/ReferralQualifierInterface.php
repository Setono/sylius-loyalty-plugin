<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Referral;

use Sylius\Component\Core\Model\OrderInterface;

interface ReferralQualifierInterface
{
    /**
     * Runs referral qualification for an order that reached the award moment. Safe to call
     * repeatedly — only the referee's first post-attribution order can decide, once.
     */
    public function qualify(OrderInterface $order): void;

    /**
     * Qualifies and rewards a referral directly, bypassing the fraud checks — the admin
     * override path. Rewarding stays idempotent per (account, referral).
     */
    public function requalify(\Setono\SyliusLoyaltyPlugin\Model\ReferralInterface $referral): void;
}
