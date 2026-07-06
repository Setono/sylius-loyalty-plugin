<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Ledger;

use PHPUnit\Framework\TestCase;
use Setono\SyliusLoyaltyPlugin\Ledger\LotReplayer;
use Setono\SyliusLoyaltyPlugin\Ledger\LotState;
use Setono\SyliusLoyaltyPlugin\Ledger\ReplayResult;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\ExpireLoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransaction;

final class LotReplayerTest extends TestCase
{
    /**
     * @test
     */
    public function it_opens_a_lot_per_credit_and_derives_the_balance(): void
    {
        $result = (new LotReplayer())->replay([
            self::credit(1, '2026-01-01', 100),
            self::credit(2, '2026-01-02', 50),
        ]);

        self::assertCount(2, $result->lots);
        self::assertSame(100, $result->lots[0]->getRemaining());
        self::assertSame(50, $result->lots[1]->getRemaining());
        self::assertSame(150, $result->balance);
        self::assertSame(0, $result->deficit);
        self::assertSame([], $result->anomalies);
    }

    /**
     * @test
     */
    public function it_consumes_the_earliest_expiring_lot_first(): void
    {
        // The older lot never expires; the newer lot expires soon and must be consumed first
        $neverExpires = self::credit(1, '2026-01-01', 100);
        $expiresSoon = self::credit(2, '2026-01-02', 100, '2026-06-01');

        $result = (new LotReplayer())->replay([
            $neverExpires,
            $expiresSoon,
            self::debit(3, '2026-01-03', 60),
        ]);

        self::assertSame(100, self::lotState($result, $neverExpires)->getRemaining());
        self::assertSame(40, self::lotState($result, $expiresSoon)->getRemaining());
        self::assertSame(140, $result->balance);
    }

    /**
     * @test
     */
    public function it_spans_a_debit_across_multiple_lots(): void
    {
        $first = self::credit(1, '2026-01-01', 100, '2026-03-01');
        $second = self::credit(2, '2026-01-02', 100, '2026-04-01');
        $debit = self::debit(3, '2026-01-03', 150);

        $result = (new LotReplayer())->replay([$first, $second, $debit]);

        self::assertSame(0, self::lotState($result, $first)->getRemaining());
        self::assertSame(50, self::lotState($result, $second)->getRemaining());

        $firstConsumptions = self::lotState($result, $first)->getConsumptions();
        self::assertCount(1, $firstConsumptions);
        self::assertSame($debit, $firstConsumptions[0]->debit);
        self::assertSame(100, $firstConsumptions[0]->points);

        $secondConsumptions = self::lotState($result, $second)->getConsumptions();
        self::assertCount(1, $secondConsumptions);
        self::assertSame($debit, $secondConsumptions[0]->debit);
        self::assertSame(50, $secondConsumptions[0]->points);
    }

    /**
     * @test
     */
    public function it_zeroes_exactly_the_referenced_lot_on_expiration_and_records_the_remaining_before(): void
    {
        $lot = self::credit(1, '2026-01-01', 100, '2026-02-01');
        $other = self::credit(2, '2026-01-02', 100);

        $result = (new LotReplayer())->replay([
            $lot,
            $other,
            self::debit(3, '2026-01-03', 30),
            self::expiration(4, '2026-02-02', 70, $lot),
        ]);

        self::assertSame(0, self::lotState($result, $lot)->getRemaining());
        self::assertTrue(self::lotState($result, $lot)->isClosedByExpiration());
        self::assertSame(100, self::lotState($result, $other)->getRemaining());

        self::assertCount(1, $result->expirations);
        self::assertSame(70, $result->expirations[0]->remainingBefore);
        self::assertSame(100, $result->balance);
    }

    /**
     * @test
     */
    public function it_carries_a_deficit_into_subsequently_opened_lots(): void
    {
        $debit = self::debit(2, '2026-01-02', 80);
        $lot = self::credit(3, '2026-01-03', 100);

        $result = (new LotReplayer())->replay([
            self::credit(1, '2026-01-01', 50, '2026-02-01'),
            $debit,
            $lot,
        ]);

        // 50 consumed from the first lot, 30 carried into the next one
        self::assertSame(70, self::lotState($result, $lot)->getRemaining());
        self::assertSame(0, $result->deficit);
        self::assertSame(70, $result->balance);

        $consumptions = self::lotState($result, $lot)->getConsumptions();
        self::assertCount(1, $consumptions);
        self::assertSame($debit, $consumptions[0]->debit);
        self::assertSame(30, $consumptions[0]->points);
    }

    /**
     * @test
     */
    public function it_reports_an_unserved_deficit(): void
    {
        $result = (new LotReplayer())->replay([
            self::credit(1, '2026-01-01', 50),
            self::debit(2, '2026-01-02', 80),
        ]);

        self::assertSame(30, $result->deficit);
        self::assertSame(-30, $result->balance);
    }

    /**
     * @test
     */
    public function it_does_not_consume_lots_already_expired_at_the_debits_occurrence(): void
    {
        // The lot expired on 2026-02-01 but the expiry cron has not closed it yet; a debit
        // after that date must not consume it
        $expired = self::credit(1, '2026-01-01', 100, '2026-02-01');

        $result = (new LotReplayer())->replay([
            $expired,
            self::debit(2, '2026-03-01', 60),
        ]);

        self::assertSame(100, self::lotState($result, $expired)->getRemaining());
        self::assertSame(60, $result->deficit);
        self::assertSame(40, $result->balance);
    }

    /**
     * @test
     */
    public function it_reports_an_anomaly_for_an_expiration_referencing_an_unknown_lot(): void
    {
        $unknownLot = self::credit(99, '2026-01-01', 10);

        $result = (new LotReplayer())->replay([
            self::credit(1, '2026-01-01', 100),
            self::expiration(2, '2026-01-02', 10, $unknownLot),
        ]);

        self::assertCount(1, $result->anomalies);
        self::assertStringContainsString('id: 2', $result->anomalies[0]);
    }

    /**
     * @test
     */
    public function it_sorts_input_into_replay_order(): void
    {
        $lot = self::credit(1, '2026-01-01', 100, '2026-02-01');

        // Deliberately shuffled input
        $result = (new LotReplayer())->replay([
            self::debit(3, '2026-01-03', 30),
            $lot,
            self::debit(2, '2026-01-02', 20),
        ]);

        self::assertSame(50, self::lotState($result, $lot)->getRemaining());
        self::assertSame(0, $result->deficit);
        self::assertSame(50, $result->balance);
    }

    /**
     * @test
     */
    public function it_uses_occurrence_then_id_as_tiebreaker_in_consumption_order(): void
    {
        // Same expiry: the earlier-occurred lot is consumed first; same occurrence: lower id first
        $first = self::credit(2, '2026-01-01', 10, '2026-06-01');
        $second = self::credit(5, '2026-01-01', 10, '2026-06-01');
        $third = self::credit(7, '2026-01-02', 10, '2026-06-01');

        $result = (new LotReplayer())->replay([
            $third,
            $second,
            $first,
            self::debit(9, '2026-01-03', 15),
        ]);

        self::assertSame(0, self::lotState($result, $first)->getRemaining());
        self::assertSame(5, self::lotState($result, $second)->getRemaining());
        self::assertSame(10, self::lotState($result, $third)->getRemaining());
    }

    private static function lotState(ReplayResult $result, EarnActionLoyaltyTransaction $lot): LotState
    {
        $lotState = $result->getLotState($lot);
        self::assertNotNull($lotState);

        return $lotState;
    }

    private static function credit(int $id, string $occurredAt, int $points, ?string $expiresAt = null): EarnActionLoyaltyTransaction
    {
        $credit = new EarnActionLoyaltyTransaction();
        $credit->setPoints($points);
        $credit->setOccurredAt(new \DateTimeImmutable($occurredAt));
        $credit->setExpiresAt(null === $expiresAt ? null : new \DateTimeImmutable($expiresAt));
        self::setId($credit, $id);

        return $credit;
    }

    private static function debit(int $id, string $occurredAt, int $points): RedeemLoyaltyTransaction
    {
        $debit = new RedeemLoyaltyTransaction();
        $debit->setPoints(-$points);
        $debit->setOccurredAt(new \DateTimeImmutable($occurredAt));
        self::setId($debit, $id);

        return $debit;
    }

    private static function expiration(int $id, string $occurredAt, int $points, EarnActionLoyaltyTransaction $lot): ExpireLoyaltyTransaction
    {
        $expiration = new ExpireLoyaltyTransaction();
        $expiration->setPoints(-$points);
        $expiration->setOccurredAt(new \DateTimeImmutable($occurredAt));
        $expiration->setLot($lot);
        self::setId($expiration, $id);

        return $expiration;
    }

    private static function setId(LoyaltyTransaction $transaction, int $id): void
    {
        $reflection = new \ReflectionProperty(LoyaltyTransaction::class, 'id');
        $reflection->setValue($transaction, $id);
    }
}
