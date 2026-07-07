<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Tier;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\SyliusLoyaltyPlugin\Event\TierChanged;
use Setono\SyliusLoyaltyPlugin\Event\TierChanging;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgram;
use Setono\SyliusLoyaltyPlugin\Model\Tier;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Repository\TierRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Tier\EvaluationWindowResolverInterface;
use Setono\SyliusLoyaltyPlugin\Tier\QualificationBasis\TierQualificationBasisInterface;
use Setono\SyliusLoyaltyPlugin\Tier\QualificationBasis\TierQualificationBasisRegistryInterface;
use Setono\SyliusLoyaltyPlugin\Tier\TierEvaluator;
use Sylius\Component\Core\Model\ChannelInterface;

final class TierEvaluatorTest extends TestCase
{
    use ProphecyTrait;

    private const POINTS_EARNED = 'points_earned';

    /**
     * @test
     */
    public function it_upgrades_immediately_when_crossing_a_threshold(): void
    {
        $account = $this->account();
        $silver = $this->tier('silver', position: 1, threshold: 1000);
        $gold = $this->tier('gold', position: 2, threshold: 5000);

        $dispatched = [];
        $evaluator = $this->evaluator([$gold, $silver], metric: 1200, dispatched: $dispatched);

        $evaluator->evaluate($account);

        self::assertSame($silver, $account->getTier());
        self::assertNull($account->getTierBelowThresholdSince());
        self::assertInstanceOf(TierChanging::class, $dispatched[0]);
        self::assertInstanceOf(TierChanged::class, $dispatched[1]);
        self::assertSame($silver, $dispatched[1]->to);
    }

    /**
     * @test
     */
    public function it_picks_the_highest_qualifying_tier(): void
    {
        $account = $this->account();
        $silver = $this->tier('silver', position: 1, threshold: 1000);
        $gold = $this->tier('gold', position: 2, threshold: 5000);

        $dispatched = [];
        $evaluator = $this->evaluator([$gold, $silver], metric: 9000, dispatched: $dispatched);

        $evaluator->evaluate($account);

        self::assertSame($gold, $account->getTier());
    }

    /**
     * @test
     */
    public function it_never_downgrades_inline(): void
    {
        $gold = $this->tier('gold', position: 2, threshold: 5000);
        $silver = $this->tier('silver', position: 1, threshold: 1000);

        $account = $this->account();
        $account->setTier($gold);

        $dispatched = [];
        $evaluator = $this->evaluator([$gold, $silver], metric: 1200, dispatched: $dispatched);

        $evaluator->evaluate($account);

        self::assertSame($gold, $account->getTier());
        self::assertSame([], $dispatched);
    }

    /**
     * @test
     */
    public function it_keeps_the_current_tier_when_the_change_is_cancelled(): void
    {
        $account = $this->account();
        $silver = $this->tier('silver', position: 1, threshold: 1000);

        $dispatched = [];
        $evaluator = $this->evaluator([$silver], metric: 1200, dispatched: $dispatched, cancel: true);

        $evaluator->evaluate($account);

        self::assertNull($account->getTier());
    }

    /**
     * @test
     */
    public function it_downgrades_immediately_at_reconciliation_without_grace(): void
    {
        $gold = $this->tier('gold', position: 2, threshold: 5000);
        $silver = $this->tier('silver', position: 1, threshold: 1000);

        $account = $this->account();
        $account->setTier($gold);

        $dispatched = [];
        $evaluator = $this->evaluator([$gold, $silver], metric: 1200, dispatched: $dispatched, graceDays: 0);

        $evaluator->reconcile($account, new \DateTimeImmutable('2026-07-06 03:00:00'));

        self::assertSame($silver, $account->getTier());
        self::assertNull($account->getTierBelowThresholdSince());
    }

    /**
     * @test
     */
    public function it_keeps_the_tier_within_the_grace_period(): void
    {
        $gold = $this->tier('gold', position: 2, threshold: 5000);
        $silver = $this->tier('silver', position: 1, threshold: 1000);

        $account = $this->account();
        $account->setTier($gold);

        $dispatched = [];
        $evaluator = $this->evaluator([$gold, $silver], metric: 1200, dispatched: $dispatched, graceDays: 30);

        $firstRun = new \DateTimeImmutable('2026-07-06 03:00:00');
        $evaluator->reconcile($account, $firstRun);

        self::assertSame($gold, $account->getTier());
        self::assertSame($firstRun, $account->getTierBelowThresholdSince());

        // Still within grace 29 days later
        $evaluator->reconcile($account, new \DateTimeImmutable('2026-08-04 03:00:00'));
        self::assertSame($gold, $account->getTier());
    }

    /**
     * @test
     */
    public function it_downgrades_after_the_grace_period(): void
    {
        $gold = $this->tier('gold', position: 2, threshold: 5000);
        $silver = $this->tier('silver', position: 1, threshold: 1000);

        $account = $this->account();
        $account->setTier($gold);
        $account->setTierBelowThresholdSince(new \DateTimeImmutable('2026-06-01 03:00:00'));

        $dispatched = [];
        $evaluator = $this->evaluator([$gold, $silver], metric: 1200, dispatched: $dispatched, graceDays: 30);

        $evaluator->reconcile($account, new \DateTimeImmutable('2026-07-06 03:00:00'));

        self::assertSame($silver, $account->getTier());
        self::assertNull($account->getTierBelowThresholdSince());
    }

    /**
     * @test
     */
    public function it_clears_the_grace_clock_when_requalified(): void
    {
        $gold = $this->tier('gold', position: 2, threshold: 5000);

        $account = $this->account();
        $account->setTier($gold);
        $account->setTierBelowThresholdSince(new \DateTimeImmutable('2026-06-20 03:00:00'));

        $dispatched = [];
        $evaluator = $this->evaluator([$gold], metric: 6000, dispatched: $dispatched, graceDays: 30);

        $evaluator->reconcile($account, new \DateTimeImmutable('2026-07-06 03:00:00'));

        self::assertSame($gold, $account->getTier());
        self::assertNull($account->getTierBelowThresholdSince());
    }

    private function account(): LoyaltyAccount
    {
        $account = new LoyaltyAccount();
        $account->setChannel($this->prophesize(ChannelInterface::class)->reveal());

        return $account;
    }

    private function tier(string $code, int $position, int $threshold): Tier
    {
        $tier = new Tier();
        $tier->setCode($code);
        $tier->setPosition($position);
        $tier->setThreshold($threshold);
        $tier->setQualificationBasis(self::POINTS_EARNED);

        return $tier;
    }

    /**
     * @param list<Tier> $tiers ordered by position, highest first
     * @param list<object> $dispatched collects dispatched events
     */
    private function evaluator(array $tiers, int $metric, array &$dispatched, int $graceDays = 0, bool $cancel = false): TierEvaluator
    {
        $tierRepository = $this->prophesize(TierRepositoryInterface::class);
        $tierRepository->findQualifiable(Argument::any())->willReturn($tiers);

        $basis = $this->prophesize(TierQualificationBasisInterface::class);
        $basis->getCode()->willReturn(self::POINTS_EARNED);
        $basis->calculate(Argument::cetera())->willReturn($metric);

        $registry = $this->prophesize(TierQualificationBasisRegistryInterface::class);
        $registry->get(self::POINTS_EARNED)->willReturn($basis->reveal());

        $program = new LoyaltyProgram();
        $program->setTierDowngradeGraceDays($graceDays);

        $programProvider = $this->prophesize(LoyaltyProgramProviderInterface::class);
        $programProvider->getByChannel(Argument::any())->willReturn($program);

        $windowResolver = $this->prophesize(EvaluationWindowResolverInterface::class);
        $windowResolver->resolve($program)->willReturn(null);

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::type('object'))->will(function (array $args) use (&$dispatched, $cancel): object {
            $event = $args[0];
            \assert(is_object($event));
            if ($cancel && $event instanceof TierChanging) {
                $event->cancel();
            }
            $dispatched[] = $event;

            return $event;
        });

        return new TierEvaluator(
            $tierRepository->reveal(),
            $registry->reveal(),
            $programProvider->reveal(),
            $windowResolver->reveal(),
            $eventDispatcher->reveal(),
        );
    }
}
