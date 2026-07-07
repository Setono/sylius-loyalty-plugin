<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\AdminUserInterface;

/**
 * A manual adjustment (credit or debit) written through the admin ledger, with a reason code
 * (§10 config list), a mandatory note, and the admin user who made it.
 */
interface ManualLoyaltyTransactionInterface extends LoyaltyTransactionInterface
{
    public function getReason(): ?string;

    public function setReason(?string $reason): void;

    public function getNote(): ?string;

    public function setNote(?string $note): void;

    public function getAdminUser(): ?AdminUserInterface;

    public function setAdminUser(?AdminUserInterface $adminUser): void;
}
