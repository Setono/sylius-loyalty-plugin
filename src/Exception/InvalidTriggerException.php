<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Exception;

/**
 * Thrown at container compile time when a configured trigger event class does not extend
 * EarningTriggerEvent or its trigger code collides with another registered trigger.
 */
final class InvalidTriggerException extends \LogicException implements ExceptionInterface
{
}
