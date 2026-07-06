<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Twig;

use Setono\SyliusLoyaltyPlugin\Provider\Shop\CartRedemptionViewProviderInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class LoyaltyExtension extends AbstractExtension
{
    public function __construct(
        private readonly CartRedemptionViewProviderInterface $cartRedemptionViewProvider,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'setono_sylius_loyalty_cart_redemption',
                $this->cartRedemptionViewProvider->getView(...),
            ),
        ];
    }
}
