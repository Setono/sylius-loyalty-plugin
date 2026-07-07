<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Exception;

/**
 * Base class for invalid-argument style exceptions in this plugin.
 *
 * Specific exceptions extend this class; it is intentionally not final.
 */
class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
}
