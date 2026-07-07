<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Replay;

use PHPUnit\Framework\TestCase;
use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\ExpireLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Replay\LotReplayer;

final class LotReplayerTest extends TestCase
{
    /**
     * @test
     */
    public function a_single_credit_opens_one_open_lot(): void
    {
        $result = (new LotReplayer())->replay([
            $this->credit(100, '2026-01-01'),
        ]);

        self::assertSame(100, $result->getBalance());
        self::assertCount(1, $result->getOpenLots());
        self::assertSame(100, $result->getOpenLots()[0]->getRemaining());
        self::assertSame(0, $result->getDeficit());
    }

    /**
     * @test
     */
    public function a_debit_reduces_the_lot_remaining(): void
    {
        $result = (new LotReplayer())->replay([
            $this->credit(100, '2026-01-01'),
            $this->redeem(-30, '2026-01-02'),
        ]);

        self::assertSame(70, $result->getBalance());
        self::assertSame(70, $result->getOpenLots()[0]->getRemaining());
    }

    /**
     * @test
     */
    public function debits_consume_the_earliest_expiring_lot_first(): void
    {
        $expiresLater = $this->credit(100, '2026-01-01', '2027-06-01');
        $expiresSooner = $this->credit(100, '2026-01-02', '2027-01-01');

        $result = (new LotReplayer())->replay([
            $expiresLater,
            $expiresSooner,
            $this->redeem(-100, '2026-02-01'),
        ]);

        self::assertSame(100, $result->getBalance());

        // lots keep their open order (by occurredAt); the sooner-expiring one is fully consumed
        $remainingByExpiry = [];
        foreach ($result->getLots() as $lot) {
            $remainingByExpiry[(string) $lot->getExpiresAt()?->format('Y-m-d')] = $lot->getRemaining();
        }
        self::assertSame(['2027-06-01' => 100, '2027-01-01' => 0], $remainingByExpiry);
    }

    /**
     * @test
     */
    public function never_expiring_lots_are_consumed_last(): void
    {
        $neverExpires = $this->credit(100, '2026-01-01');
        $expires = $this->credit(100, '2026-01-02', '2027-01-01');

        $result = (new LotReplayer())->replay([
            $neverExpires,
            $expires,
            $this->redeem(-100, '2026-02-01'),
        ]);

        $remaining = [];
        foreach ($result->getLots() as $lot) {
            $remaining[null === $lot->getExpiresAt() ? 'never' : 'expires'] = $lot->getRemaining();
        }
        self::assertSame(['never' => 100, 'expires' => 0], $remaining);
    }

    /**
     * @test
     */
    public function a_debit_spanning_lots_drains_them_in_order(): void
    {
        $result = (new LotReplayer())->replay([
            $this->credit(50, '2026-01-01', '2027-01-01'),
            $this->credit(50, '2026-01-02', '2027-02-01'),
            $this->redeem(-70, '2026-03-01'),
        ]);

        self::assertSame(30, $result->getBalance());
        self::assertCount(1, $result->getOpenLots());
        self::assertSame(30, $result->getOpenLots()[0]->getRemaining());
        self::assertSame('2027-02-01', $result->getOpenLots()[0]->getExpiresAt()?->format('Y-m-d'));
    }

    /**
     * @test
     */
    public function an_expire_transaction_zeroes_exactly_its_lot(): void
    {
        $lot = $this->credit(100, '2026-01-01', '2027-01-01');
        $otherLot = $this->credit(40, '2026-01-02');

        $result = (new LotReplayer())->replay([
            $lot,
            $otherLot,
            $this->expire($lot, -100, '2027-01-02'),
        ]);

        self::assertSame(40, $result->getBalance());
        self::assertCount(1, $result->getOpenLots());
        self::assertSame(40, $result->getOpenLots()[0]->getRemaining());
    }

    /**
     * @test
     */
    public function a_debit_exceeding_open_credit_carries_a_deficit_consumed_by_the_next_credit(): void
    {
        $result = (new LotReplayer())->replay([
            $this->credit(50, '2026-01-01'),
            $this->redeem(-80, '2026-01-02'),
            $this->credit(100, '2026-01-03'),
        ]);

        self::assertSame(70, $result->getBalance());
        self::assertSame(0, $result->getDeficit());
        // only the second credit has points left, reduced by the 30 deficit
        $openLots = $result->getOpenLots();
        self::assertCount(1, $openLots);
        self::assertSame(70, $openLots[0]->getRemaining());
    }

    /**
     * @test
     */
    public function it_sums_points_expiring_at_or_before_a_moment(): void
    {
        $result = (new LotReplayer())->replay([
            $this->credit(100, '2026-01-01', '2026-02-01'),
            $this->credit(200, '2026-01-02', '2026-06-01'),
            $this->credit(50, '2026-01-03'),
        ]);

        self::assertSame(100, $result->getPointsExpiringAtOrBefore(new \DateTimeImmutable('2026-03-01')));
        self::assertSame(300, $result->getPointsExpiringAtOrBefore(new \DateTimeImmutable('2026-07-01')));
    }

    private function credit(int $points, string $occurredAt, ?string $expiresAt = null): EarnOrderLoyaltyTransaction
    {
        $credit = new EarnOrderLoyaltyTransaction();
        $credit->setPoints($points);
        $credit->setOccurredAt(new \DateTimeImmutable($occurredAt));
        if (null !== $expiresAt) {
            $credit->setExpiresAt(new \DateTimeImmutable($expiresAt));
        }

        return $credit;
    }

    private function redeem(int $points, string $occurredAt): RedeemLoyaltyTransaction
    {
        $redeem = new RedeemLoyaltyTransaction();
        $redeem->setPoints($points);
        $redeem->setOccurredAt(new \DateTimeImmutable($occurredAt));

        return $redeem;
    }

    private function expire(CreditLoyaltyTransactionInterface $lot, int $points, string $occurredAt): ExpireLoyaltyTransaction
    {
        $expire = new ExpireLoyaltyTransaction();
        $expire->setPoints($points);
        $expire->setOccurredAt(new \DateTimeImmutable($occurredAt));
        $expire->setLot($lot);

        return $expire;
    }
}
