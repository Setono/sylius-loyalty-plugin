<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Referral;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Model\ReferralInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Referral\FraudCheck\CompositeReferralFraudCheckInterface;
use Setono\SyliusLoyaltyPlugin\Repository\ReferralRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * Referral qualification at the award moment (§8): the referee's first post-attribution order
 * decides. Items total below the minimum or any fraud flag ends the referral; clean
 * qualification credits both parties (a disabled account is skipped, not deferred) and marks
 * it rewarded. Rewarding is idempotent per (account, referral) at the database level.
 */
final class ReferralQualifier implements ReferralQualifierInterface
{
    public function __construct(
        private readonly ReferralRepositoryInterface $referralRepository,
        private readonly LoyaltyAccountProviderInterface $accountProvider,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly CompositeReferralFraudCheckInterface $fraudCheck,
        private readonly LoyaltyLedgerInterface $ledger,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function qualify(OrderInterface $order): void
    {
        $customer = $order->getCustomer();
        $channel = $order->getChannel();
        if (!$customer instanceof CustomerInterface || !$channel instanceof ChannelInterface) {
            return;
        }

        $referral = $this->referralRepository->findOneByRefereeAndChannel($customer, $channel);
        if (null === $referral || ReferralInterface::STATUS_PENDING !== $referral->getStatus()) {
            return;
        }

        // Only orders placed after attribution count; a pre-registration guest order never
        // qualifies (deliberately decoupled from retroactive guest points)
        $placedAt = $order->getCheckoutCompletedAt();
        $capturedAt = $referral->getCreatedAt();
        if (null === $placedAt || null === $capturedAt || $placedAt < $capturedAt) {
            return;
        }

        // The FIRST order to reach the award moment decides — later orders never re-qualify
        if (null === $referral->getRefereeFirstOrder()) {
            $referral->setRefereeFirstOrder($order);
        } elseif ($referral->getRefereeFirstOrder() !== $order) {
            return;
        }

        $program = $this->programProvider->getByChannel($channel);

        if ($order->getItemsTotal() < $program->getReferralMinOrderTotal()) {
            // Below the minimum: the decision stands (stays pending until expiry)
            $this->entityManager->flush();

            return;
        }

        $flags = $this->fraudCheck->checkAll($referral, $order);
        if ([] !== $flags) {
            $referral->setStatus(ReferralInterface::STATUS_REJECTED);
            $referral->setFraudFlags(array_map(
                static fn ($flag) => $flag->toArray(),
                $flags,
            ));
            $this->entityManager->flush();

            $this->logger->info(sprintf(
                '[Loyalty] Referral %d rejected by fraud checks: %s',
                (int) $referral->getId(),
                implode(', ', array_map(static fn ($flag) => $flag->check, $flags)),
            ));

            return;
        }

        $this->requalify($referral);
    }

    public function requalify(ReferralInterface $referral): void
    {
        $channel = $referral->getChannel();
        if (!$channel instanceof ChannelInterface) {
            return;
        }

        $referral->setStatus(ReferralInterface::STATUS_QUALIFIED);
        $referral->setQualifiedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        $this->reward($referral, $this->programProvider->getByChannel($channel), $channel);
    }

    private function reward(ReferralInterface $referral, LoyaltyProgramInterface $program, ChannelInterface $channel): void
    {
        $expiresAt = null;
        if (null !== $program->getPointsExpiryDays()) {
            $expiresAt = new \DateTimeImmutable(sprintf('+%d days', $program->getPointsExpiryDays()));
        }

        $referrerAccount = $referral->getReferrerAccount();
        if (null !== $referrerAccount && $referrerAccount->isEnabled() && $program->getReferralReferrerPoints() > 0) {
            $this->ledger->earnReferral($referrerAccount, $program->getReferralReferrerPoints(), $referral, $expiresAt);
        }

        $referee = $referral->getRefereeCustomer();
        if ($referee instanceof CustomerInterface && $program->getReferralRefereePoints() > 0) {
            $refereeAccount = $this->accountProvider->getByCustomerAndChannel($referee, $channel);
            if ($refereeAccount->isEnabled()) {
                $this->ledger->earnReferral($refereeAccount, $program->getReferralRefereePoints(), $referral, $expiresAt);
            }
        }

        $referral->setStatus(ReferralInterface::STATUS_REWARDED);
        $this->entityManager->flush();
    }
}
