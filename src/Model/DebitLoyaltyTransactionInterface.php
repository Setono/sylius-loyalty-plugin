<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

/**
 * A debit consumes open lots in consumption order (expiresAt ASC NULLS LAST, occurredAt ASC,
 * id ASC), derived by replay.
 */
interface DebitLoyaltyTransactionInterface extends LoyaltyTransactionInterface
{
}
