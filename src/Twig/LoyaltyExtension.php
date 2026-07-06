<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Provider\Shop\CartRedemptionViewProviderInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

// todo: Use a Twig runtime instead of injecting deps into this constructor
final class LoyaltyExtension extends AbstractExtension
{
    public function __construct(
        private readonly CartRedemptionViewProviderInterface $cartRedemptionViewProvider,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'setono_sylius_loyalty_cart_redemption',
                $this->cartRedemptionViewProvider->getView(...),
            ),
            new TwigFunction(
                'setono_sylius_loyalty_transaction_type',
                $this->transactionType(...),
            ),
        ];
    }

    /**
     * The transaction's discriminator value (e.g. "earn_order", "redeem").
     */
    public function transactionType(LoyaltyTransactionInterface $transaction): string
    {
        $discriminator = $this->entityManager->getClassMetadata($transaction::class)->discriminatorValue;

        return is_string($discriminator) ? $discriminator : $transaction::class;
    }
}
