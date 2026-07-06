<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\AdminUserInterface;

/**
 * A manual adjustment written from the admin panel: a reason code (configured via the
 * "setono_sylius_loyalty.manual_adjustment_reasons" bundle config), a mandatory note, and the
 * admin user who made the adjustment.
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
