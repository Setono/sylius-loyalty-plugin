<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Exception;

/**
 * Thrown when a ledger write cannot proceed because the ledger is in a conflicting state, e.g.
 * a rollback for a redemption that was already rolled back.
 */
final class LedgerConflictException extends \RuntimeException implements ExceptionInterface
{
}
