<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Exception;

/**
 * Marker interface implemented by every exception thrown by this plugin.
 *
 * Consumers can catch all plugin exceptions with a single `catch (ExceptionInterface $e)`
 * block, or catch a specific subtype for finer-grained handling.
 */
interface ExceptionInterface extends \Throwable
{
}
