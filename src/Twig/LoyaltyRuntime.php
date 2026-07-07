<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Hint\EarnHintCalculatorInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Provider\Shop\CartRedemptionView;
use Setono\SyliusLoyaltyPlugin\Provider\Shop\CartRedemptionViewProviderInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class LoyaltyRuntime implements RuntimeExtensionInterface
{
    /**
     * @param class-string<LoyaltyAccountInterface> $accountClass
     */
    public function __construct(
        private readonly CartRedemptionViewProviderInterface $cartRedemptionViewProvider,
        private readonly LoyaltyTransactionRepositoryInterface $transactionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly EarnHintCalculatorInterface $earnHintCalculator,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly ChannelContextInterface $channelContext,
        private readonly CustomerContextInterface $customerContext,
        private readonly string $accountClass,
    ) {
    }

    public function cartRedemption(OrderInterface $cart): ?CartRedemptionView
    {
        return $this->cartRedemptionViewProvider->getView($cart);
    }

    /**
     * The transaction's discriminator value (e.g. "earn_order", "redeem").
     */
    public function transactionType(LoyaltyTransactionInterface $transaction): string
    {
        $discriminator = $this->entityManager->getClassMetadata($transaction::class)->discriminatorValue;

        return is_string($discriminator) ? $discriminator : $transaction::class;
    }

    /**
     * @return list<LoyaltyAccountInterface>
     */
    public function accountsOf(CustomerInterface $customer): array
    {
        /** @var list<LoyaltyAccountInterface> $accounts */
        $accounts = $this->entityManager->getRepository($this->accountClass)->findBy(['customer' => $customer]);

        return $accounts;
    }

    /**
     * The account's latest transactions, newest first.
     *
     * @return list<LoyaltyTransactionInterface>
     */
    public function latestTransactions(LoyaltyAccountInterface $account, int $limit = 25): array
    {
        return array_slice(array_reverse($this->transactionRepository->findForReplay($account)), 0, $limit);
    }

    /**
     * Per-variant earn hint for the product page, or null when hidden (toggle off, no
     * applicable rules, disabled account).
     *
     * @return array{default: int, variants: array<string, int|null>}|null
     */
    public function productEarnHint(ProductInterface $product): ?array
    {
        $channel = $this->channelContext->getChannel();
        if (!$channel instanceof ChannelInterface || !$this->programProvider->getByChannel($channel)->isShowEarnableOnProduct()) {
            return null;
        }

        $customer = $this->customerContext->getCustomer();
        $customer = $customer instanceof CustomerInterface ? $customer : null;

        $variants = [];
        $default = null;
        foreach ($product->getEnabledVariants() as $variant) {
            \assert($variant instanceof ProductVariantInterface);
            $points = $this->earnHintCalculator->forVariant($variant, $channel, $customer);
            $variants[(string) $variant->getCode()] = $points;
            $default ??= $points;
        }

        return null === $default ? null : ['default' => $default, 'variants' => $variants];
    }

    /**
     * "This order earns ~N points", or null when hidden.
     */
    public function cartEarnHint(OrderInterface $cart): ?int
    {
        $channel = $cart->getChannel();
        if (!$channel instanceof ChannelInterface || !$this->programProvider->getByChannel($channel)->isShowEarnableInCart()) {
            return null;
        }

        $customer = $cart->getCustomer();

        return $this->earnHintCalculator->forCart($cart, $customer instanceof CustomerInterface ? $customer : null);
    }
}
