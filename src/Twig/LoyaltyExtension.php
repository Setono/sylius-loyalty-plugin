<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class LoyaltyExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('setono_sylius_loyalty_cart_redemption', [LoyaltyRuntime::class, 'cartRedemption']),
            new TwigFunction('setono_sylius_loyalty_transaction_type', [LoyaltyRuntime::class, 'transactionType']),
            new TwigFunction('setono_sylius_loyalty_accounts', [LoyaltyRuntime::class, 'accountsOf']),
            new TwigFunction('setono_sylius_loyalty_latest_transactions', [LoyaltyRuntime::class, 'latestTransactions']),
        ];
    }
}
