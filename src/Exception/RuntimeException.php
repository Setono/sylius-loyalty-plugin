<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Exception;

/**
 * Base class for runtime exceptions in this plugin.
 *
 * Specific exceptions (e.g. insufficient balance, ledger conflict) extend this class;
 * it is intentionally not final.
 */
class RuntimeException extends \RuntimeException implements ExceptionInterface
{
}
