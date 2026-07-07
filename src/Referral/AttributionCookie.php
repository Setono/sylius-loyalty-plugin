<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Referral;

use Symfony\Component\HttpFoundation\Cookie;

/**
 * The referral attribution cookie: 30 days (sessions don't reliably live that long), last
 * click wins.
 */
final class AttributionCookie
{
    public const NAME = 'setono_sylius_loyalty_ref';

    public const TTL_DAYS = 30;

    public static function create(string $code): Cookie
    {
        return Cookie::create(self::NAME)
            ->withValue($code)
            ->withExpires(new \DateTimeImmutable(sprintf('+%d days', self::TTL_DAYS)))
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX)
        ;
    }

    /**
     * A cheap format check before any database lookup: 8 chars of Crockford base32.
     */
    public static function isValidFormat(string $code): bool
    {
        return 1 === preg_match('/^[0-9A-HJKMNP-TV-Z]{8}$/', $code);
    }
}
