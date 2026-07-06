<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression\Function;

use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductInterface;

/**
 * The eligible basis (minor units) of the order items whose product carries the given taxon.
 */
final class TaxonTotalFunction implements ExpressionFunctionInterface
{
    public function getName(): string
    {
        return 'taxon_total';
    }

    public function getSignature(): string
    {
        return 'taxon_total(taxonCode: string): int';
    }

    public function getDescription(): string
    {
        return 'setono_sylius_loyalty.expression.function.taxon_total';
    }

    public function evaluate(EarningContext $context, mixed ...$arguments): mixed
    {
        $taxonCode = $arguments[0] ?? null;
        if (!is_string($taxonCode) || null === $context->order) {
            return 0;
        }

        $total = 0;
        foreach ($context->order->getItems() as $item) {
            if ($item instanceof OrderItemInterface && self::hasTaxon($item, $taxonCode)) {
                $total += $context->itemAmounts[(int) $item->getId()] ?? 0;
            }
        }

        return $total;
    }

    private static function hasTaxon(OrderItemInterface $item, string $taxonCode): bool
    {
        $product = $item->getProduct();
        if (!$product instanceof ProductInterface) {
            return false;
        }

        foreach ($product->getTaxons() as $taxon) {
            if ($taxon->getCode() === $taxonCode) {
                return true;
            }
        }

        return false;
    }
}
