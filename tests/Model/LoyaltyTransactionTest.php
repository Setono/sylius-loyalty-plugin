<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Model;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Model\ClawbackLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\ExpireLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\ManualCreditLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\ManualDebitLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\RedeemRollbackLoyaltyTransaction;
use Sylius\Component\Core\Model\OrderInterface;

final class LoyaltyTransactionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function every_concrete_type_has_its_snake_case_discriminator(): void
    {
        self::assertSame('earn_order', EarnOrderLoyaltyTransaction::getType());
        self::assertSame('earn_action', EarnActionLoyaltyTransaction::getType());
        self::assertSame('redeem_rollback', RedeemRollbackLoyaltyTransaction::getType());
        self::assertSame('manual_credit', ManualCreditLoyaltyTransaction::getType());
        self::assertSame('redeem', RedeemLoyaltyTransaction::getType());
        self::assertSame('manual_debit', ManualDebitLoyaltyTransaction::getType());
        self::assertSame('expire', ExpireLoyaltyTransaction::getType());
        self::assertSame('clawback', ClawbackLoyaltyTransaction::getType());
    }

    /**
     * @test
     */
    public function a_transaction_carries_an_account_signed_points_and_a_timestamp(): void
    {
        $account = $this->prophesize(LoyaltyAccountInterface::class)->reveal();
        $occurredAt = new \DateTimeImmutable('2026-01-01 10:00:00');

        $transaction = new EarnOrderLoyaltyTransaction();
        $transaction->setAccount($account);
        $transaction->setPoints(150);
        $transaction->setOccurredAt($occurredAt);

        self::assertNull($transaction->getId());
        self::assertSame($account, $transaction->getAccount());
        self::assertSame(150, $transaction->getPoints());
        self::assertSame($occurredAt, $transaction->getOccurredAt());
    }

    /**
     * @test
     */
    public function a_credit_lot_carries_an_expiry(): void
    {
        $expiresAt = new \DateTimeImmutable('2027-01-01');

        $credit = new EarnOrderLoyaltyTransaction();
        self::assertNull($credit->getExpiresAt());

        $credit->setExpiresAt($expiresAt);
        self::assertSame($expiresAt, $credit->getExpiresAt());
    }

    /**
     * @test
     */
    public function an_earn_order_carries_the_order_basis_and_rules_breakdown(): void
    {
        $order = $this->prophesize(OrderInterface::class)->reveal();

        $transaction = new EarnOrderLoyaltyTransaction();
        self::assertSame(0, $transaction->getBasisAmount());
        self::assertSame([], $transaction->getRulesBreakdown());

        $transaction->setOrder($order);
        $transaction->setBasisAmount(10000);
        $transaction->setRulesBreakdown(['1' => 100]);

        self::assertSame($order, $transaction->getOrder());
        self::assertSame(10000, $transaction->getBasisAmount());
        self::assertSame(['1' => 100], $transaction->getRulesBreakdown());
    }

    /**
     * @test
     */
    public function a_manual_transaction_carries_a_reason_note_and_admin_user(): void
    {
        $transaction = new ManualCreditLoyaltyTransaction();
        $transaction->setReason('goodwill');
        $transaction->setNote('Sorry for the trouble');

        self::assertSame('goodwill', $transaction->getReason());
        self::assertSame('Sorry for the trouble', $transaction->getNote());
        self::assertNull($transaction->getAdminUser());
    }
}
