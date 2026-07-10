<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Earning;

use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Repository\EarningRuleRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Rule\Amount\EarningAmountContext;
use Setono\SyliusLoyaltyPlugin\Rule\EarningAmountEvaluatorInterface;
use Setono\SyliusLoyaltyPlugin\Rule\EarningConditionEvaluatorInterface;
use Setono\SyliusLoyaltyPlugin\Rule\RuleEvaluationContext;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

final class ActionPointsAwarder implements ActionPointsAwarderInterface
{
    public function __construct(
        private readonly LoyaltyAccountProviderInterface $accountProvider,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly EarningRuleRepositoryInterface $ruleRepository,
        private readonly EarningConditionEvaluatorInterface $conditionEvaluator,
        private readonly EarningAmountEvaluatorInterface $amountEvaluator,
        private readonly LoyaltyLedgerInterface $ledger,
    ) {
    }

    public function award(
        CustomerInterface $customer,
        ChannelInterface $channel,
        string $trigger,
        string $sourceIdentifier,
        ?\DateTimeInterface $awardedAt = null,
    ): void {
        $account = $this->accountProvider->getAccount($customer, $channel);
        if (!$account->isEnabled()) {
            return;
        }

        $awardedAt ??= new \DateTimeImmutable();

        // A customer action has no order; the date/day conditions still evaluate off $awardedAt.
        $context = new RuleEvaluationContext($channel, $awardedAt, null, $customer);
        $amountContext = new EarningAmountContext(basis: 0, quantity: 1);

        $points = 0;
        $breakdown = [];
        foreach ($this->ruleRepository->findEnabledByChannelAndTrigger($channel, $trigger) as $rule) {
            if (!$this->conditionEvaluator->matches($rule, $context)) {
                continue;
            }

            $rulePoints = $this->amountEvaluator->calculate($rule, $amountContext);
            if ($rulePoints > 0) {
                $points += $rulePoints;
                $breakdown[(int) $rule->getId()] = $rulePoints;
            }
        }

        if ($points <= 0) {
            return;
        }

        $program = $this->programProvider->getProgram($channel);
        $this->ledger->earnForAction($account, $sourceIdentifier, $points, $breakdown, $this->expiresAt($program, $awardedAt));
    }

    private function expiresAt(LoyaltyProgramInterface $program, \DateTimeInterface $from): ?\DateTimeInterface
    {
        $days = $program->getPointsExpiryDays();
        if (null === $days || $days <= 0) {
            return null;
        }

        return \DateTimeImmutable::createFromInterface($from)->add(new \DateInterval(sprintf('P%dD', $days)));
    }
}
