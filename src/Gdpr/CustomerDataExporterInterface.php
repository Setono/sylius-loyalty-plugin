<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Gdpr;

use Sylius\Component\Core\Model\CustomerInterface;

interface CustomerDataExporterInterface
{
    /**
     * Builds a complete, JSON-serializable snapshot of a customer's loyalty data — one entry per
     * channel account with its cached totals and its full ledger history — for a GDPR data-access
     * request.
     *
     * @return array{
     *     customer: array<string, mixed>,
     *     accounts: list<array{
     *         channel: string|null,
     *         enabled: bool,
     *         balance: int,
     *         lifetimeEarned: int,
     *         referralCode: string|null,
     *         transactions: list<array<string, mixed>>,
     *     }>,
     * }
     */
    public function export(CustomerInterface $customer): array;
}
