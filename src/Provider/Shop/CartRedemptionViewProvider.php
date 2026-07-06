<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider\Shop;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyOrderInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Redemption\AppliedPointsProviderInterface;
use Setono\SyliusLoyaltyPlugin\Redemption\PointsConverterInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class CartRedemptionViewProvider implements CartRedemptionViewProviderInterface
{
    public function __construct(
        private readonly LoyaltyAccountRepositoryInterface $accountRepository,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly PointsConverterInterface $pointsConverter,
        private readonly AppliedPointsProviderInterface $appliedPointsProvider,
    ) {
    }

    public function getView(OrderInterface $cart): ?CartRedemptionView
    {
        if (!$cart instanceof LoyaltyOrderInterface) {
            return null;
        }

        $customer = $cart->getCustomer();
        $channel = $cart->getChannel();
        if (!$customer instanceof CustomerInterface || null === $channel) {
            return null;
        }

        $account = $this->accountRepository->findOneByCustomerAndChannel($customer, $channel);
        if (null === $account || !$account->isEnabled()) {
            return null;
        }

        $program = $this->programProvider->getByChannel($channel);
        $appliedPoints = $this->appliedPointsProvider->getAppliedPoints($cart);

        if ($account->getBalance() < $program->getMinRedeemPoints() && $appliedPoints <= 0) {
            return null;
        }

        return new CartRedemptionView(
            $account->getBalance(),
            $program->getMinRedeemPoints(),
            $this->presets($cart, $account->getBalance(), $program),
            $cart->getLoyaltyPointsRequested() ?? 0,
            $appliedPoints,
            $this->pointsConverter->amountFromPoints($appliedPoints, $program),
        );
    }

    /**
     * Up to three preset steps between the redemption minimum and the currently usable
     * maximum, each a clean multiple of the conversion so it maps to a clean currency amount.
     *
     * @return list<array{points: int, amount: int}>
     */
    private function presets(OrderInterface $cart, int $balance, LoyaltyProgramInterface $program): array
    {
        $capPoints = $this->pointsConverter->pointsFromAmount(
            (int) floor($cart->getItemsTotal() * $program->getMaxRedeemPercentOfOrder() / 100),
            $program,
        );

        $maxUsable = $this->pointsConverter->clampToCleanMultiple(min($balance, $capPoints), $program);

        $pointsUnit = max(1, $program->getRedemptionConversionPoints());
        $minimum = $program->getMinRedeemPoints();
        $minimum += ($pointsUnit - $minimum % $pointsUnit) % $pointsUnit;

        $candidates = [
            $minimum,
            $this->pointsConverter->clampToCleanMultiple(intdiv($maxUsable, 2), $program),
            $this->pointsConverter->clampToCleanMultiple(intdiv($maxUsable * 3, 4), $program),
        ];

        $presets = [];
        foreach ($candidates as $points) {
            if ($points < $minimum || $points > $maxUsable || $points > $balance) {
                continue;
            }

            $presets[$points] = [
                'points' => $points,
                'amount' => $this->pointsConverter->amountFromPoints($points, $program),
            ];
        }

        ksort($presets);

        return array_values($presets);
    }
}
