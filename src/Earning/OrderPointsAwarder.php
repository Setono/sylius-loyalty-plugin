<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Earning;

use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Repository\EarningRuleRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Rule\Basis\EarningBasisProviderInterface;
use Setono\SyliusLoyaltyPlugin\Rule\EarningCalculatorInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class OrderPointsAwarder implements OrderPointsAwarderInterface
{
    public const TRIGGER = 'order_eligible';

    public function __construct(
        private readonly LoyaltyAccountProviderInterface $accountProvider,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly EarningRuleRepositoryInterface $ruleRepository,
        private readonly EarningCalculatorInterface $calculator,
        private readonly EarningBasisProviderInterface $basisProvider,
        private readonly LoyaltyLedgerInterface $ledger,
    ) {
    }

    public function award(OrderInterface $order, ?\DateTimeInterface $awardedAt = null): void
    {
        $customer = $order->getCustomer();
        $channel = $order->getChannel();
        if (!$customer instanceof CustomerInterface || null === $channel) {
            return;
        }

        $account = $this->accountProvider->getAccount($customer, $channel);
        if (!$account->isEnabled()) {
            return;
        }

        $program = $this->programProvider->getProgram($channel);
        $rules = $this->ruleRepository->findEnabledByChannelAndTrigger($channel, self::TRIGGER);

        $awardedAt ??= new \DateTimeImmutable();
        $result = $this->calculator->calculate($order, $program, $rules, $awardedAt);
        if ($result->points <= 0) {
            return;
        }

        $this->ledger->earnForOrder(
            $account,
            $order,
            $result->points,
            $this->basisProvider->getBasis($order, $program),
            $result->breakdown,
            $this->expiresAt($program, $awardedAt),
        );
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
