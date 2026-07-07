<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\OrderProcessing;

use Setono\SyliusLoyaltyPlugin\LoyaltyAdjustmentTypes;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyOrderInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Redemption\PointsConverterInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Sylius\Component\Core\Distributor\IntegerDistributorInterface;
use Sylius\Component\Core\Distributor\ProportionalIntegerDistributorInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Order\Factory\AdjustmentFactoryInterface;
use Sylius\Component\Order\Model\OrderInterface as BaseOrderInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

/**
 * Derives the applied redemption from the customer's persisted request on every order
 * recalculation: applied = min(requested, balance, cap) clamped down to a clean multiple of
 * the conversion's points unit, written as unit-distributed negative adjustments so taxes
 * compute on the reduced base natively. Positioned after the promotion processors and before
 * the tax processor. The stored request is never overwritten by clamping — if the cart
 * shrinks the adjustment shrinks, and it grows back toward the request automatically.
 */
final class LoyaltyRedemptionProcessor implements OrderProcessorInterface
{
    /**
     * @param AdjustmentFactoryInterface<\Sylius\Component\Order\Model\AdjustmentInterface> $adjustmentFactory
     */
    public function __construct(
        private readonly LoyaltyAccountRepositoryInterface $accountRepository,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly PointsConverterInterface $pointsConverter,
        private readonly AdjustmentFactoryInterface $adjustmentFactory,
        private readonly ProportionalIntegerDistributorInterface $proportionalDistributor,
        private readonly IntegerDistributorInterface $integerDistributor,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function process(BaseOrderInterface $order): void
    {
        Assert::isInstanceOf($order, OrderInterface::class);

        if (OrderInterface::STATE_CART !== $order->getState()) {
            return;
        }

        $order->removeAdjustmentsRecursively(LoyaltyAdjustmentTypes::REDEMPTION);

        if (!$order instanceof LoyaltyOrderInterface) {
            return;
        }

        $requested = $order->getLoyaltyPointsRequested() ?? 0;
        if ($requested <= 0) {
            return;
        }

        $customer = $order->getCustomer();
        $channel = $order->getChannel();
        if (!$customer instanceof CustomerInterface || null === $channel) {
            return;
        }

        $account = $this->accountRepository->findOneByCustomerAndChannel($customer, $channel);
        if (null === $account || !$account->isEnabled()) {
            return;
        }

        $program = $this->programProvider->getByChannel($channel);

        $applied = $this->appliedPoints($requested, $account->getBalance(), $order, $program);
        if ($applied <= 0) {
            return;
        }

        $this->distribute($order, -$this->pointsConverter->amountFromPoints($applied, $program), $applied);
    }

    private function appliedPoints(int $requested, int $balance, OrderInterface $order, LoyaltyProgramInterface $program): int
    {
        $capAmount = (int) floor($order->getItemsTotal() * $program->getMaxRedeemPercentOfOrder() / 100);
        $capPoints = $this->pointsConverter->pointsFromAmount($capAmount, $program);

        return $this->pointsConverter->clampToCleanMultiple(min($requested, $balance, $capPoints), $program);
    }

    /**
     * Distributes the discount across the order items' units following Sylius' order-promotion
     * pattern, which is what makes VAT calculate on the net amount natively.
     */
    private function distribute(OrderInterface $order, int $discount, int $appliedPoints): void
    {
        $items = [];
        $itemTotals = [];
        foreach ($order->getItems() as $item) {
            if (!$item instanceof OrderItemInterface) {
                continue;
            }

            $items[] = $item;
            $itemTotals[] = $item->getTotal();
        }

        if ([] === $items) {
            return;
        }

        $label = $this->translator->trans('setono_sylius_loyalty.ui.points_redemption');

        $itemShares = $this->proportionalDistributor->distribute($itemTotals, $discount);
        foreach ($items as $index => $item) {
            $itemShare = $itemShares[$index] ?? 0;
            if (!is_int($itemShare) || 0 === $itemShare) {
                continue;
            }

            $unitShares = $this->integerDistributor->distribute($itemShare, $item->getQuantity());
            $unitIndex = 0;
            foreach ($item->getUnits() as $unit) {
                $amount = $unitShares[$unitIndex] ?? 0;
                ++$unitIndex;
                if (!is_int($amount) || 0 === $amount) {
                    continue;
                }

                $adjustment = $this->adjustmentFactory->createWithData(
                    LoyaltyAdjustmentTypes::REDEMPTION,
                    $label,
                    $amount,
                );
                $adjustment->setDetails([
                    'appliedPoints' => $appliedPoints,
                ]);

                $unit->addAdjustment($adjustment);
            }
        }
    }
}
