<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Hint;

use Setono\SyliusLoyaltyPlugin\EarningRule\Basis\EligibleBasisCalculatorInterface;
use Setono\SyliusLoyaltyPlugin\EarningRule\Checker\ConditionCheckerRegistryInterface;
use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;
use Setono\SyliusLoyaltyPlugin\EarningRule\EarningRuleEvaluatorInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Repository\EarningRuleRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * The read-only evaluation path behind the earn hints (§9): the same rule engine, filtered so
 * a hint may understate but never overstate. Rules whose conditions need what the hint
 * context lacks are excluded; expression-mode rules are excluded for guests (their
 * expressions may reference the missing customer).
 */
final class EarnHintCalculator implements EarnHintCalculatorInterface
{
    public function __construct(
        private readonly EarningRuleRepositoryInterface $ruleRepository,
        private readonly ConditionCheckerRegistryInterface $conditionCheckers,
        private readonly EligibleBasisCalculatorInterface $basisCalculator,
        private readonly EarningRuleEvaluatorInterface $evaluator,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly LoyaltyAccountRepositoryInterface $accountRepository,
        private readonly SyntheticCartFactoryInterface $syntheticCartFactory,
    ) {
    }

    public function forVariant(ProductVariantInterface $variant, ChannelInterface $channel, ?CustomerInterface $customer): ?int
    {
        $cart = $this->syntheticCartFactory->create($variant, $channel);
        if (null === $cart) {
            return null;
        }

        return $this->evaluate($cart, $channel, $customer, syntheticCart: true);
    }

    public function forCart(OrderInterface $cart, ?CustomerInterface $customer): ?int
    {
        $channel = $cart->getChannel();
        if (!$channel instanceof ChannelInterface || $cart->isEmpty()) {
            return null;
        }

        return $this->evaluate($cart, $channel, $customer, syntheticCart: false);
    }

    private function evaluate(OrderInterface $cart, ChannelInterface $channel, ?CustomerInterface $customer, bool $syntheticCart): ?int
    {
        $account = null;
        if ($customer instanceof CustomerInterface) {
            $account = $this->accountRepository->findOneByCustomerAndChannel($customer, $channel);
            // Hints are hidden entirely for customers whose account is disabled
            if (null !== $account && !$account->isEnabled()) {
                return null;
            }
        }

        $rules = array_values(array_filter(
            $this->ruleRepository->findForEvaluation($channel, EarningRuleInterface::TRIGGER_ORDER_ELIGIBLE),
            fn (EarningRuleInterface $rule): bool => $this->isEvaluable($rule, null !== $customer, $syntheticCart),
        ));
        if ([] === $rules) {
            return null;
        }

        $program = $this->programProvider->getByChannel($channel);
        $basis = $this->basisCalculator->calculate($cart, $program);

        $context = new EarningContext(
            channel: $channel,
            customer: $customer,
            account: $account,
            order: $cart,
            itemAmounts: $basis->itemAmounts,
            extraAmount: $basis->extraAmount,
        );

        $points = $this->evaluator->evaluate($rules, $context, $program)->points;

        return $points > 0 ? $points : null;
    }

    private function isEvaluable(EarningRuleInterface $rule, bool $hasCustomer, bool $syntheticCart): bool
    {
        if ($rule->isDryRun()) {
            return false;
        }

        if (!$hasCustomer && 'expression' === $rule->getAmountType()) {
            return false;
        }

        foreach ($rule->getConditions() as $condition) {
            $checker = $this->conditionCheckers->get((string) $condition->getType());
            if (null === $checker) {
                return false;
            }

            if ($checker->requiresCustomer() && !$hasCustomer) {
                return false;
            }

            if ($checker->requiresCart() && $syntheticCart) {
                return false;
            }
        }

        return true;
    }
}
