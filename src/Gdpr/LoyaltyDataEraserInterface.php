<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Gdpr;

use Sylius\Component\Core\Model\CustomerInterface;

interface LoyaltyDataEraserInterface
{
    /**
     * Permanently removes all of a customer's loyalty data — every channel account and its complete
     * ledger history — for a GDPR erasure request. The append-only ledger invariant does not apply
     * here: erasure is an explicit, deliberate exception.
     *
     * @return int the number of accounts erased
     */
    public function erase(CustomerInterface $customer): int;
}
