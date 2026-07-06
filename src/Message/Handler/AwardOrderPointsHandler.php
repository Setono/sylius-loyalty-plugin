<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Message\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Setono\SyliusLoyaltyPlugin\EarningRule\Basis\EligibleBasisCalculatorInterface;
use Setono\SyliusLoyaltyPlugin\EarningRule\DryRunLoggerInterface;
use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;
use Setono\SyliusLoyaltyPlugin\EarningRule\EarningRuleEvaluatorInterface;
use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Message\AwardOrderPoints;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Referral\ReferralQualifierInterface;
use Setono\SyliusLoyaltyPlugin\Repository\EarningRuleRepositoryInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderPaymentStates;

final class AwardOrderPointsHandler
{
    /**
     * @param class-string $orderClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly LoyaltyAccountProviderInterface $accountProvider,
        private readonly EarningRuleRepositoryInterface $ruleRepository,
        private readonly EligibleBasisCalculatorInterface $basisCalculator,
        private readonly EarningRuleEvaluatorInterface $evaluator,
        private readonly DryRunLoggerInterface $dryRunLogger,
        private readonly LoyaltyLedgerInterface $ledger,
        private readonly ReferralQualifierInterface $referralQualifier,
        private readonly LoggerInterface $logger,
        private readonly string $orderClass,
    ) {
    }

    public function __invoke(AwardOrderPoints $message): void
    {
        $order = $this->entityManager->find($this->orderClass, $message->orderId);
        if (!$order instanceof OrderInterface) {
            $this->logger->info(sprintf('[Loyalty] Order %d no longer exists; nothing to award', $message->orderId));

            return;
        }

        $customer = $order->getCustomer();
        $channel = $order->getChannel();
        if (!$customer instanceof CustomerInterface || null === $channel) {
            // Guest checkout — guests earn nothing at order time (retroactive claim on registration)
            return;
        }

        $program = $this->programProvider->getByChannel($channel);
        if (!$this->awardMomentReached($order, $program)) {
            return;
        }

        // Referral qualification shares the award moment; it decides once per referral and is
        // independent of whether any earning rules exist
        $this->referralQualifier->qualify($order);

        $rules = $this->ruleRepository->findForEvaluation($channel, EarningRuleInterface::TRIGGER_ORDER_ELIGIBLE);
        if ([] === $rules) {
            return;
        }

        $account = $this->accountProvider->getByCustomerAndChannel($customer, $channel);
        if (!$account->isEnabled()) {
            return;
        }

        $basis = $this->basisCalculator->calculate($order, $program);

        $context = new EarningContext(
            channel: $channel,
            customer: $customer,
            account: $account,
            order: $order,
            itemAmounts: $basis->itemAmounts,
            extraAmount: $basis->extraAmount,
        );

        $result = $this->evaluator->evaluate($rules, $context, $program);

        $this->dryRunLogger->log($result, $account, $order);

        if ($result->points <= 0) {
            return;
        }

        $this->ledger->earnOrder(
            $order,
            $result->points,
            $result->rulesBreakdown,
            $basis->getTotal(),
            self::expiresAt($program),
        );
    }

    private function awardMomentReached(OrderInterface $order, LoyaltyProgramInterface $program): bool
    {
        if (LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_ORDER_FULFILLED === $program->getAwardOrderPointsAt()) {
            return OrderInterface::STATE_FULFILLED === $order->getState();
        }

        // Handles partial payments: award only when the whole order is paid
        return OrderPaymentStates::STATE_PAID === $order->getPaymentState();
    }

    private static function expiresAt(LoyaltyProgramInterface $program): ?\DateTimeImmutable
    {
        $days = $program->getPointsExpiryDays();

        return null === $days ? null : new \DateTimeImmutable(sprintf('+%d days', $days));
    }
}
