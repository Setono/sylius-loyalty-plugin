<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

abstract class CreditLoyaltyTransaction extends LoyaltyTransaction implements CreditLoyaltyTransactionInterface
{
    protected ?\DateTimeImmutable $expiresAt = null;

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }
}
