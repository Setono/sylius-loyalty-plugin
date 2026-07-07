<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression\Function;

use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;
use Sylius\Component\Core\Model\OrderItemInterface;

/**
 * The eligible basis (minor units) of the order items with the given product code.
 */
final class ProductTotalFunction implements ExpressionFunctionInterface
{
    public function getName(): string
    {
        return 'product_total';
    }

    public function getSignature(): string
    {
        return 'product_total(productCode: string): int';
    }

    public function getDescription(): string
    {
        return 'setono_sylius_loyalty.expression.function.product_total';
    }

    public function evaluate(EarningContext $context, mixed ...$arguments): mixed
    {
        $productCode = $arguments[0] ?? null;
        if (!is_string($productCode) || null === $context->order) {
            return 0;
        }

        $total = 0;
        foreach ($context->order->getItems() as $item) {
            if ($item instanceof OrderItemInterface && $item->getProduct()?->getCode() === $productCode) {
                $total += $context->itemAmounts[(int) $item->getId()] ?? 0;
            }
        }

        return $total;
    }
}
