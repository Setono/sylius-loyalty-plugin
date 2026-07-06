<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Checker;

use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;

final class OrderTotalAtLeastConditionChecker implements ConditionCheckerInterface
{
    public const TYPE = 'order_total_at_least';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return 'setono_sylius_loyalty.form.earning_rule.condition.order_total_at_least';
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

        $amount = $configuration['amount'] ?? null;
        if (!is_int($amount)) {
            return false;
        }

        return $context->order->getTotal() >= $amount;
    }
}
