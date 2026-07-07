<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Exception;

/**
 * Thrown when an ExpressionLanguage expression fails to parse or references variables,
 * properties, or functions outside the sandbox whitelist.
 */
final class InvalidExpressionException extends \InvalidArgumentException implements ExceptionInterface
{
}
