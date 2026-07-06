<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Ledger;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;
use Setono\SyliusLoyaltyPlugin\Event\AwardingPoints;
use Setono\SyliusLoyaltyPlugin\Event\PointsEarned;
use Setono\SyliusLoyaltyPlugin\Exception\InsufficientBalanceException;
use Setono\SyliusLoyaltyPlugin\Exception\LedgerConflictException;
use Setono\SyliusLoyaltyPlugin\Ledger\LotReplayer;
use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedger;
use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgram;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\ManualDebitLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Tier\TierEvaluatorInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class LoyaltyLedgerTest extends TestCase
{
    use ProphecyTrait;

    private LoyaltyAccount $account;

    private LoyaltyProgram $program;

    private EventDispatcher $eventDispatcher;

    /** @var ObjectProphecy<EntityManagerInterface> */
    private ObjectProphecy $entityManager;

    /** @var ObjectProphecy<LoyaltyTransactionRepositoryInterface> */
    private ObjectProphecy $transactionRepository;

    /** @var ObjectProphecy<TierEvaluatorInterface> */
    private ObjectProphecy $tierEvaluator;

    /** @var list<LoyaltyTransactionInterface> */
    private array $persistedTransactions = [];

    protected function setUp(): void
    {
        $this->account = new LoyaltyAccount();
        $reflection = new \ReflectionProperty(LoyaltyAccount::class, 'id');
        $reflection->setValue($this->account, 1);

        $this->program = new LoyaltyProgram();
        $this->eventDispatcher = new EventDispatcher();
        $this->persistedTransactions = [];

        $repository = $this->prophesize(EntityRepository::class);
        $repository->find(1, LockMode::PESSIMISTIC_WRITE)->willReturn($this->account);

        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->entityManager->getRepository(LoyaltyAccount::class)->willReturn($repository->reveal());
        $this->entityManager->wrapInTransaction(Argument::type('callable'))->will(
            static function (array $args): mixed {
                $callback = $args[0];
                \assert(is_callable($callback));

                return $callback();
            },
        );
        $persisted = &$this->persistedTransactions;
        $this->entityManager->persist(Argument::type(LoyaltyTransaction::class))->will(
            static function (array $args) use (&$persisted): void {
                $persisted[] = $args[0];
            },
        );

        $this->transactionRepository = $this->prophesize(LoyaltyTransactionRepositoryInterface::class);
        $this->transactionRepository->findRedeemTransaction(Argument::any())->willReturn(null);
        $this->transactionRepository->findForReplay(Argument::any())->willReturn([]);

        $this->tierEvaluator = $this->prophesize(TierEvaluatorInterface::class);
    }

    /**
     * @test
     */
    public function it_earns_points_for_an_order(): void
    {
        $pointsEarnedEvents = [];
        $this->eventDispatcher->addListener(PointsEarned::class, static function (PointsEarned $event) use (&$pointsEarnedEvents): void {
            $pointsEarnedEvents[] = $event;
        });

        $expiresAt = new \DateTimeImmutable('+365 days');
        $transaction = $this->ledger()->earnOrder($this->order(), 100, ['rule:1' => 100], 10000, $expiresAt);

        self::assertInstanceOf(EarnOrderLoyaltyTransaction::class, $transaction);
        self::assertSame(100, $transaction->getPoints());
        self::assertSame($expiresAt, $transaction->getExpiresAt());
        self::assertSame(10000, $transaction->getBasisAmount());
        self::assertSame($this->account, $transaction->getAccount());
        self::assertSame(100, $this->account->getBalance());
        self::assertSame(100, $this->account->getLifetimeEarned());
        self::assertCount(1, $pointsEarnedEvents);
        $this->tierEvaluator->evaluate($this->account)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function it_skips_earning_for_a_disabled_account(): void
    {
        $this->account->setEnabled(false);

        self::assertNull($this->ledger()->earnOrder($this->order(), 100));
        self::assertSame(0, $this->account->getBalance());
        self::assertSame([], $this->persistedTransactions);
    }

    /**
     * @test
     */
    public function it_lets_listeners_adjust_and_cancel_awards(): void
    {
        $this->eventDispatcher->addListener(AwardingPoints::class, static function (AwardingPoints $event): void {
            $event->cancel();
        });

        self::assertNull($this->ledger()->earnOrder($this->order(), 100));
        self::assertSame(0, $this->account->getBalance());
        self::assertSame([], $this->persistedTransactions);
    }

    /**
     * @test
     */
    public function it_debits_redeemed_points(): void
    {
        $this->account->setBalance(500);

        $transaction = $this->ledger()->redeem($this->order(), 300);

        self::assertInstanceOf(RedeemLoyaltyTransaction::class, $transaction);
        self::assertSame(-300, $transaction->getPoints());
        self::assertSame(200, $this->account->getBalance());
        // Redeeming never touches lifetime earned
        self::assertSame(0, $this->account->getLifetimeEarned());
    }

    /**
     * @test
     */
    public function it_throws_when_the_balance_is_insufficient_for_redemption(): void
    {
        $this->account->setBalance(100);

        $this->expectException(InsufficientBalanceException::class);

        $this->ledger()->redeem($this->order(), 300);
    }

    /**
     * @test
     */
    public function it_refuses_redemption_for_a_disabled_account(): void
    {
        $this->account->setBalance(500);
        $this->account->setEnabled(false);

        $this->expectException(LedgerConflictException::class);

        $this->ledger()->redeem($this->order(), 300);
    }

    /**
     * @test
     */
    public function it_claws_back_the_points_earned_for_an_order(): void
    {
        $this->account->setBalance(500);
        $order = $this->order();

        $earn = new EarnOrderLoyaltyTransaction();
        $earn->setAccount($this->account);
        $earn->setPoints(100);
        $this->transactionRepository->findEarnOrderTransaction($order)->willReturn($earn);

        $transaction = $this->ledger()->clawback($order, 100);

        self::assertNotNull($transaction);
        self::assertSame(-100, $transaction->getPoints());
        self::assertSame($earn, $transaction->getEarn());
        self::assertSame(400, $this->account->getBalance());
    }

    /**
     * @test
     */
    public function it_clamps_a_clawback_to_zero_under_the_clamp_policy(): void
    {
        $this->account->setBalance(30);
        $this->program->setClawbackPolicy(LoyaltyProgram::CLAWBACK_POLICY_CLAMP_TO_ZERO);
        $order = $this->order();

        $earn = new EarnOrderLoyaltyTransaction();
        $earn->setAccount($this->account);
        $earn->setPoints(100);
        $this->transactionRepository->findEarnOrderTransaction($order)->willReturn($earn);

        $transaction = $this->ledger()->clawback($order, 100);

        self::assertNotNull($transaction);
        self::assertSame(-30, $transaction->getPoints());
        self::assertSame(0, $this->account->getBalance());
    }

    /**
     * @test
     */
    public function it_is_a_noop_when_the_order_earned_nothing(): void
    {
        $order = $this->order();
        $this->transactionRepository->findEarnOrderTransaction($order)->willReturn(null);

        self::assertNull($this->ledger()->clawback($order, 100));
    }

    /**
     * @test
     */
    public function it_allows_manual_adjustments_on_disabled_accounts(): void
    {
        $this->account->setBalance(100);
        $this->account->setEnabled(false);

        $transaction = $this->ledger()->manualDebit($this->account, 40, 'correction', 'Support case #42');

        self::assertInstanceOf(ManualDebitLoyaltyTransaction::class, $transaction);
        self::assertSame(-40, $transaction->getPoints());
        self::assertSame('correction', $transaction->getReason());
        self::assertSame('Support case #42', $transaction->getNote());
        self::assertSame(60, $this->account->getBalance());
    }

    private function ledger(): LoyaltyLedger
    {
        $accountProvider = $this->prophesize(LoyaltyAccountProviderInterface::class);
        $accountProvider->getByCustomerAndChannel(Argument::any(), Argument::any())->willReturn($this->account);

        $programProvider = $this->prophesize(LoyaltyProgramProviderInterface::class);
        $programProvider->getByChannel(Argument::any())->willReturn($this->program);

        return new LoyaltyLedger(
            $this->entityManager->reveal(),
            $this->prophesize(ManagerRegistry::class)->reveal(),
            $accountProvider->reveal(),
            $this->transactionRepository->reveal(),
            new LotReplayer(),
            $programProvider->reveal(),
            $this->tierEvaluator->reveal(),
            $this->eventDispatcher,
            new NullLogger(),
            LoyaltyAccount::class,
        );
    }

    private function order(): OrderInterface
    {
        $channel = $this->prophesize(ChannelInterface::class);
        $customer = $this->prophesize(CustomerInterface::class);

        $order = $this->prophesize(OrderInterface::class);
        $order->getCustomer()->willReturn($customer->reveal());
        $order->getChannel()->willReturn($channel->reveal());
        $order->getNumber()->willReturn('000000001');

        $this->account->setChannel($channel->reveal());

        return $order->reveal();
    }
}
