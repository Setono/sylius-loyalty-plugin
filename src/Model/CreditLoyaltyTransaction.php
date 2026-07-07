<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

abstract class CreditLoyaltyTransaction extends LoyaltyTransaction implements CreditLoyaltyTransactionInterface
{
    protected ?\DateTimeInterface $expiresAt = null;

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }
}
