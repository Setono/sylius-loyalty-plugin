<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule\Amount;

/**
 * A base earning amount — computes the points a rule contributes for a given basis. Projects add their
 * own by implementing this interface and tagging the service `setono_sylius_loyalty.earning_amount`
 * (autoconfigured). Multiplier amounts are resolved separately, since they scale the summed base
 * result rather than producing points from a basis (§3.4).
 */
interface EarningAmountInterface
{
    /**
     * The amount's type discriminator, matched against an EarningRule's amountType.
     */
    public function getType(): string;

    /**
     * @param array<string, mixed> $configuration the rule's amount configuration
     *
     * @return int the points contributed (never negative)
     */
    public function calculate(EarningAmountContext $context, array $configuration): int;
}
