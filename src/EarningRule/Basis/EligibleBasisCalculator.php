<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Basis;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;

final class EligibleBasisCalculator implements EligibleBasisCalculatorInterface
{
    public function calculate(OrderInterface $order, LoyaltyProgramInterface $program): EligibleBasis
    {
        $includeTaxes = $program->isIncludeTaxes();

        $itemAmounts = [];
        $itemsSum = 0;
        foreach ($order->getItems() as $item) {
            if (!$item instanceof OrderItemInterface || null === $item->getId()) {
                continue;
            }

            // getTotal() is the discounted amount: unit prices, distributed promotions, the
            // distributed loyalty redemption, and non-neutral taxes; getTaxTotal() covers both
            // included (neutral) and added (non-neutral) taxes
            $amount = $item->getTotal();
            if (!$includeTaxes) {
                $amount -= $item->getTaxTotal();
            }

            $amount = max(0, $amount);
            $itemAmounts[(int) $item->getId()] = $amount;
            $itemsSum += $amount;
        }

        if (LoyaltyProgramInterface::EARNING_BASIS_ITEMS_TOTAL === $program->getEarningBasis()) {
            return new EligibleBasis($itemAmounts);
        }

        $orderTotal = $order->getTotal();
        if (!$includeTaxes) {
            $orderTotal -= $order->getTaxTotal();
        }

        return new EligibleBasis($itemAmounts, max(0, $orderTotal - $itemsSum));
    }
}
