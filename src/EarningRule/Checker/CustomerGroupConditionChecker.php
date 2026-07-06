<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule\Checker;

use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;

final class CustomerGroupConditionChecker implements ConditionCheckerInterface
{
    public const TYPE = 'customer_group';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return 'setono_sylius_loyalty.form.earning_rule.condition.customer_group';
    }

    public function getConfigurationFormType(): ?string
    {
        return null; // set when the admin forms ship
    }

    public function check(array $configuration, EarningContext $context): bool
    {
        $group = $context->customer?->getGroup();
        if (null === $group) {
            return false;
        }

        /** @var list<string> $groups */
        $groups = array_values(array_filter((array) ($configuration['groups'] ?? []), is_string(...)));

        return in_array($group->getCode(), $groups, true);
    }
}
