<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Inspector;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;

interface AccountInspectorInterface
{
    public function inspect(LoyaltyAccountInterface $account, ?\DateTimeImmutable $now = null): AccountInspection;
}
