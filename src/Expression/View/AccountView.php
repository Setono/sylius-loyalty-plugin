<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression\View;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;

final class AccountView
{
    private function __construct(
        public readonly int $balance,
        public readonly int $lifetimeEarned,
        public readonly bool $enabled,
    ) {
    }

    public static function fromAccount(LoyaltyAccountInterface $account): self
    {
        return new self(
            $account->getBalance(),
            $account->getLifetimeEarned(),
            $account->isEnabled(),
        );
    }
}
