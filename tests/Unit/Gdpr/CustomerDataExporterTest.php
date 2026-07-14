<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Gdpr;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Gdpr\CustomerDataExporter;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\ManualCreditLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class CustomerDataExporterTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_exports_the_customer_identity_and_one_entry_per_account(): void
    {
        $customer = $this->prophesize(CustomerInterface::class);
        $customer->getId()->willReturn(7);
        $customer->getEmail()->willReturn('jane@example.com');
        $customer->getFirstName()->willReturn('Jane');
        $customer->getLastName()->willReturn('Doe');

        $channel = $this->prophesize(ChannelInterface::class);
        $channel->getCode()->willReturn('WEB');

        $account = $this->prophesize(LoyaltyAccountInterface::class);
        $account->getChannel()->willReturn($channel->reveal());
        $account->isEnabled()->willReturn(true);
        $account->getBalance()->willReturn(150);
        $account->getLifetimeEarned()->willReturn(300);
        $account->getReferralCode()->willReturn('ABC123');

        $accountRepository = $this->prophesize(LoyaltyAccountRepositoryInterface::class);
        $accountRepository->findByCustomer($customer->reveal())->willReturn([$account->reveal()]);

        $transactionRepository = $this->prophesize(LoyaltyTransactionRepositoryInterface::class);
        $transactionRepository->findByAccount($account->reveal())->willReturn([]);

        $data = (new CustomerDataExporter($accountRepository->reveal(), $transactionRepository->reveal()))
            ->export($customer->reveal());

        self::assertSame([
            'id' => 7,
            'email' => 'jane@example.com',
            'firstName' => 'Jane',
            'lastName' => 'Doe',
        ], $data['customer']);
        self::assertCount(1, $data['accounts']);
        self::assertSame('WEB', $data['accounts'][0]['channel']);
        self::assertSame(150, $data['accounts'][0]['balance']);
        self::assertSame(300, $data['accounts'][0]['lifetimeEarned']);
        self::assertSame('ABC123', $data['accounts'][0]['referralCode']);
        self::assertTrue($data['accounts'][0]['enabled']);
        self::assertSame([], $data['accounts'][0]['transactions']);
    }

    /**
     * @test
     */
    public function it_normalizes_the_type_specific_fields_of_each_transaction(): void
    {
        $occurredAt = new \DateTimeImmutable('2026-01-02T03:04:05+00:00');
        $expiresAt = new \DateTimeImmutable('2027-01-02T03:04:05+00:00');

        $order = $this->prophesize(OrderInterface::class);
        $order->getNumber()->willReturn('000000042');
        $order->getId()->willReturn(42);

        $earnOrder = new EarnOrderLoyaltyTransaction();
        $earnOrder->setPoints(100);
        $earnOrder->setOccurredAt($occurredAt);
        $earnOrder->setExpiresAt($expiresAt);
        $earnOrder->setOrder($order->reveal());

        $earnAction = new EarnActionLoyaltyTransaction();
        $earnAction->setPoints(25);
        $earnAction->setOccurredAt($occurredAt);
        $earnAction->setSourceIdentifier('customer_registered:7:1');

        $manual = new ManualCreditLoyaltyTransaction();
        $manual->setPoints(10);
        $manual->setOccurredAt($occurredAt);
        $manual->setReason('goodwill');
        $manual->setNote('Compensation for a delayed order');

        $customer = $this->prophesize(CustomerInterface::class);
        $customer->getId()->willReturn(7);
        $customer->getEmail()->willReturn('jane@example.com');
        $customer->getFirstName()->willReturn('Jane');
        $customer->getLastName()->willReturn('Doe');

        $account = $this->prophesize(LoyaltyAccountInterface::class);
        $account->getChannel()->willReturn(null);
        $account->isEnabled()->willReturn(true);
        $account->getBalance()->willReturn(135);
        $account->getLifetimeEarned()->willReturn(135);
        $account->getReferralCode()->willReturn(null);

        $accountRepository = $this->prophesize(LoyaltyAccountRepositoryInterface::class);
        $accountRepository->findByCustomer($customer->reveal())->willReturn([$account->reveal()]);

        $transactionRepository = $this->prophesize(LoyaltyTransactionRepositoryInterface::class);
        $transactionRepository->findByAccount($account->reveal())->willReturn([$earnOrder, $earnAction, $manual]);

        $transactions = (new CustomerDataExporter($accountRepository->reveal(), $transactionRepository->reveal()))
            ->export($customer->reveal())['accounts'][0]['transactions'];

        self::assertSame([
            'type' => 'earn_order',
            'points' => 100,
            'occurredAt' => '2026-01-02T03:04:05+00:00',
            'expiresAt' => '2027-01-02T03:04:05+00:00',
            'order' => '000000042',
        ], $transactions[0]);

        self::assertSame([
            'type' => 'earn_action',
            'points' => 25,
            'occurredAt' => '2026-01-02T03:04:05+00:00',
            'expiresAt' => null,
            'source' => 'customer_registered:7:1',
        ], $transactions[1]);

        self::assertSame([
            'type' => 'manual_credit',
            'points' => 10,
            'occurredAt' => '2026-01-02T03:04:05+00:00',
            'expiresAt' => null,
            'reason' => 'goodwill',
            'note' => 'Compensation for a delayed order',
        ], $transactions[2]);
    }

    /**
     * @test
     */
    public function it_exports_no_accounts_when_the_customer_has_none(): void
    {
        $customer = $this->prophesize(CustomerInterface::class);
        $customer->getId()->willReturn(7);
        $customer->getEmail()->willReturn('jane@example.com');
        $customer->getFirstName()->willReturn('Jane');
        $customer->getLastName()->willReturn('Doe');

        $accountRepository = $this->prophesize(LoyaltyAccountRepositoryInterface::class);
        $accountRepository->findByCustomer($customer->reveal())->willReturn([]);

        $transactionRepository = $this->prophesize(LoyaltyTransactionRepositoryInterface::class);

        $data = (new CustomerDataExporter($accountRepository->reveal(), $transactionRepository->reveal()))
            ->export($customer->reveal());

        self::assertSame([], $data['accounts']);
    }
}
