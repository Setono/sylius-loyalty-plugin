<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional\Referral;

use Setono\SyliusLoyaltyPlugin\EventListener\ClawbackListener;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\ReferralInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Referral\ReferralQualifierInterface;
use Setono\SyliusLoyaltyPlugin\Tests\Application\Entity\Order;
use Setono\SyliusLoyaltyPlugin\Tests\Functional\Earning\AwardOrderPointsTestCase;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

final class ReferralLifecycleTest extends AwardOrderPointsTestCase
{
    /**
     * @test
     */
    public function it_rewards_both_parties_once_and_claws_back_both_on_cancellation(): void
    {
        $container = self::getContainer();
        $entityManager = $this->entityManager();

        $channel = $this->channel();
        $referrer = $this->customer();
        $referee = $this->customer();

        $accountProvider = $container->get(LoyaltyAccountProviderInterface::class);
        \assert($accountProvider instanceof LoyaltyAccountProviderInterface);
        $referrerAccount = $accountProvider->getByCustomerAndChannel($referrer, $channel);

        $referral = $this->referral($referrerAccount, $referee, $channel);

        // The qualifying order: above the default 25.00 minimum
        $order = $this->paidOrder($channel, $referee, unitPrice: 5000, quantity: 1);
        $order->setCheckoutCompletedAt(new \DateTimeImmutable('+1 minute'));
        $entityManager->flush();

        $qualifier = $container->get(ReferralQualifierInterface::class);
        \assert($qualifier instanceof ReferralQualifierInterface);

        $qualifier->qualify($order);
        // A duplicate call (redelivery) must not double-reward
        $qualifier->qualify($order);

        $program = $container->get(LoyaltyProgramProviderInterface::class)->getByChannel($channel);

        self::assertSame(ReferralInterface::STATUS_REWARDED, $referral->getStatus());
        self::assertSame($program->getReferralReferrerPoints(), $referrerAccount->getBalance());

        $refereeAccount = $accountProvider->getByCustomerAndChannel($referee, $channel);
        self::assertSame($program->getReferralRefereePoints(), $refereeAccount->getBalance());

        // Cancelling the qualifying order claws back BOTH rewards
        $clawbackListener = $container->get(ClawbackListener::class);
        \assert($clawbackListener instanceof ClawbackListener);
        $clawbackListener->clawback($order);
        $clawbackListener->clawback($order); // idempotent

        $entityManager->refresh($referrerAccount);
        $entityManager->refresh($refereeAccount);
        self::assertSame(0, $referrerAccount->getBalance());
        self::assertSame(0, $refereeAccount->getBalance());
    }

    /**
     * @test
     */
    public function it_rejects_a_self_referral_by_normalized_email(): void
    {
        $container = self::getContainer();
        $entityManager = $this->entityManager();

        $channel = $this->channel();

        $referrer = $this->customer();
        $referrerEmail = (string) $referrer->getEmail();

        // Same mailbox with Gmail-style aliasing
        $referee = $this->customer();
        [$local, $domain] = explode('@', $referrerEmail, 2);
        $referee->setEmail(sprintf('%s+friend@%s', $local, $domain));
        $entityManager->flush();

        $accountProvider = $container->get(LoyaltyAccountProviderInterface::class);
        \assert($accountProvider instanceof LoyaltyAccountProviderInterface);
        $referrerAccount = $accountProvider->getByCustomerAndChannel($referrer, $channel);

        $referral = $this->referral($referrerAccount, $referee, $channel);

        $order = $this->paidOrder($channel, $referee, unitPrice: 5000, quantity: 1);
        $order->setCheckoutCompletedAt(new \DateTimeImmutable('+1 minute'));
        $entityManager->flush();

        $qualifier = $container->get(ReferralQualifierInterface::class);
        \assert($qualifier instanceof ReferralQualifierInterface);
        $qualifier->qualify($order);

        self::assertSame(ReferralInterface::STATUS_REJECTED, $referral->getStatus());
        self::assertSame('self_referral', $referral->getFraudFlags()[0]['check'] ?? null);
        self::assertSame(0, $referrerAccount->getBalance());
    }

    /**
     * @test
     */
    public function it_does_not_qualify_an_order_below_the_minimum(): void
    {
        $container = self::getContainer();
        $entityManager = $this->entityManager();

        $channel = $this->channel();
        $referrer = $this->customer();
        $referee = $this->customer();

        $accountProvider = $container->get(LoyaltyAccountProviderInterface::class);
        \assert($accountProvider instanceof LoyaltyAccountProviderInterface);
        $referrerAccount = $accountProvider->getByCustomerAndChannel($referrer, $channel);

        $referral = $this->referral($referrerAccount, $referee, $channel);

        // Below the default 25.00 minimum
        $order = $this->paidOrder($channel, $referee, unitPrice: 1000, quantity: 1);
        $order->setCheckoutCompletedAt(new \DateTimeImmutable('+1 minute'));
        $entityManager->flush();

        $qualifier = $container->get(ReferralQualifierInterface::class);
        \assert($qualifier instanceof ReferralQualifierInterface);
        $qualifier->qualify($order);

        self::assertSame(ReferralInterface::STATUS_PENDING, $referral->getStatus());
        self::assertSame($order->getId(), $referral->getRefereeFirstOrder()?->getId());

        // A later, larger order does not re-open the decision
        $secondOrder = $this->paidOrder($channel, $referee, unitPrice: 9000, quantity: 1);
        $secondOrder->setCheckoutCompletedAt(new \DateTimeImmutable('+2 minutes'));
        $entityManager->flush();
        $qualifier->qualify($secondOrder);

        self::assertSame(ReferralInterface::STATUS_PENDING, $referral->getStatus());
        self::assertSame(0, $referrerAccount->getBalance());
    }

    private function referral(LoyaltyAccountInterface $referrerAccount, CustomerInterface $referee, ChannelInterface $channel): ReferralInterface
    {
        $container = self::getContainer();

        $factory = $container->get('setono_sylius_loyalty.factory.referral');
        \assert(is_object($factory) && method_exists($factory, 'createNew'));
        $referral = $factory->createNew();
        \assert($referral instanceof ReferralInterface);

        $referral->setReferrerAccount($referrerAccount);
        $referral->setRefereeCustomer($referee);
        $referral->setChannel($channel);
        $referral->setCode('TESTCODE');

        $this->entityManager()->persist($referral);
        $this->entityManager()->flush();

        return $referral;
    }
}
