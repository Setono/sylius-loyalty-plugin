<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Promotion\Checker\Rule;

use Setono\SyliusLoyaltyPlugin\Model\TierInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Repository\TierRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Promotion\Checker\Rule\RuleCheckerInterface;
use Sylius\Component\Promotion\Model\PromotionSubjectInterface;

/**
 * "Customer's loyalty tier is at least X" — lets merchants build tier-gated promotions
 * ("Gold gets free shipping") with the stock promotion engine.
 */
final class CustomerLoyaltyTierRuleChecker implements RuleCheckerInterface
{
    public const TYPE = 'customer_loyalty_tier';

    public function __construct(
        private readonly LoyaltyAccountRepositoryInterface $accountRepository,
        private readonly TierRepositoryInterface $tierRepository,
    ) {
    }

    /**
     * @param array<array-key, mixed> $configuration
     */
    public function isEligible(PromotionSubjectInterface $subject, array $configuration): bool
    {
        if (!$subject instanceof OrderInterface) {
            return false;
        }

        $customer = $subject->getCustomer();
        $channel = $subject->getChannel();
        if (!$customer instanceof CustomerInterface || !$channel instanceof ChannelInterface) {
            return false;
        }

        $requiredCode = $configuration['tier'] ?? null;
        if (!is_string($requiredCode) || '' === $requiredCode) {
            return false;
        }

        $required = $this->tierRepository->findOneBy(['code' => $requiredCode, 'channel' => $channel]);
        if (!$required instanceof TierInterface) {
            return false;
        }

        $account = $this->accountRepository->findOneByCustomerAndChannel($customer, $channel);
        $tier = $account?->getTier();
        if (null === $tier || false === $account?->isEnabled()) {
            return false;
        }

        return $tier->getPosition() >= $required->getPosition();
    }
}
