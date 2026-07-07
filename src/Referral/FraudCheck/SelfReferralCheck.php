<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Referral\FraudCheck;

use Setono\SyliusLoyaltyPlugin\Model\ReferralInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * Flags referrals where referrer and referee are plausibly the same person: same customer,
 * same normalized email (dots and +alias stripped), or the same fuzzy default address
 * (street + postcode).
 */
final class SelfReferralCheck implements ReferralFraudCheckInterface
{
    public function check(ReferralInterface $referral, OrderInterface $order): ?FraudFlag
    {
        $referrer = $referral->getReferrerAccount()?->getCustomer();
        $referee = $referral->getRefereeCustomer();
        if (!$referrer instanceof CustomerInterface || !$referee instanceof CustomerInterface) {
            return null;
        }

        if ($referrer === $referee || $referrer->getId() === $referee->getId()) {
            return new FraudFlag('self_referral', 'Referrer and referee are the same customer');
        }

        if (null !== $referrer->getEmail() && null !== $referee->getEmail() &&
            self::normalizeEmail($referrer->getEmail()) === self::normalizeEmail($referee->getEmail())) {
            return new FraudFlag('self_referral', 'Referrer and referee emails normalize identically');
        }

        $referrerAddress = $referrer->getDefaultAddress();
        $refereeAddress = $referee->getDefaultAddress() ?? $order->getShippingAddress();
        if (null !== $referrerAddress && null !== $refereeAddress &&
            self::fuzzyAddress($referrerAddress) === self::fuzzyAddress($refereeAddress) &&
            '' !== self::fuzzyAddress($referrerAddress)) {
            return new FraudFlag('self_referral', 'Referrer and referee share an address');
        }

        return null;
    }

    private static function normalizeEmail(string $email): string
    {
        $email = mb_strtolower(trim($email));
        [$local, $domain] = explode('@', $email, 2) + [1 => ''];

        // Strip +alias and dots in the local part (Gmail-style aliasing)
        $local = explode('+', $local, 2)[0];
        $local = str_replace('.', '', $local);

        return $local . '@' . $domain;
    }

    private static function fuzzyAddress(AddressInterface $address): string
    {
        $street = mb_strtolower(preg_replace('/\s+/', '', (string) $address->getStreet()) ?? '');
        $postcode = mb_strtolower(preg_replace('/\s+/', '', (string) $address->getPostcode()) ?? '');

        return '' === $street . $postcode ? '' : $street . '|' . $postcode;
    }
}
