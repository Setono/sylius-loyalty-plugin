<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Exception;

/**
 * Marker implemented by every exception thrown by this plugin, so consumers can catch-all the
 * plugin or catch precisely.
 */
interface ExceptionInterface extends \Throwable
{
}
