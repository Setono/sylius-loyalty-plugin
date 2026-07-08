<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule\Basis;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * Computes the earning basis from an order: the items total (default) or the order total, optionally
 * net of tax. Discounts are already reflected because the underlying Sylius totals are the discounted
 * amounts (§4.2).
 */
final class EarningBasisProvider implements EarningBasisProviderInterface
{
    public function getBasis(OrderInterface $order, LoyaltyProgramInterface $program): int
    {
        if (LoyaltyProgramInterface::EARNING_BASIS_ORDER_TOTAL === $program->getEarningBasis()) {
            $basis = $order->getTotal();
            $tax = $order->getTaxTotal();
        } else {
            $basis = $order->getItemsTotal();
            $tax = $this->itemsTaxTotal($order);
        }

        if (!$program->includeTaxes()) {
            $basis -= $tax;
        }

        return max(0, $basis);
    }

    private function itemsTaxTotal(OrderInterface $order): int
    {
        $tax = 0;
        foreach ($order->getItems() as $item) {
            $tax += $item->getAdjustmentsTotalRecursively(AdjustmentInterface::TAX_ADJUSTMENT);
        }

        return $tax;
    }
}
