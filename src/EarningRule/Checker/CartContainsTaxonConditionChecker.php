<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Checker;

use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductInterface;

final class CartContainsTaxonConditionChecker implements ConditionCheckerInterface
{
    public const TYPE = 'cart_contains_taxon';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return 'setono_sylius_loyalty.form.earning_rule.condition.cart_contains_taxon';
    }

    public function getConfigurationFormType(): ?string
    {
        return null; // set when the admin forms ship
    }

    public function check(array $configuration, EarningContext $context): bool
    {
        if (null === $context->order) {
            return false;
        }

        /** @var list<string> $taxons */
        $taxons = array_values(array_filter((array) ($configuration['taxons'] ?? []), is_string(...)));
        if ([] === $taxons) {
            return false;
        }

        foreach ($context->order->getItems() as $item) {
            if (!$item instanceof OrderItemInterface) {
                continue;
            }

            $product = $item->getProduct();
            if (!$product instanceof ProductInterface) {
                continue;
            }

            foreach ($product->getTaxons() as $taxon) {
                if (in_array($taxon->getCode(), $taxons, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function requiresCustomer(): bool
    {
        return false;
    }

    public function requiresCart(): bool
    {
        return true;
    }
}
