<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tier;

use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\SyliusLoyaltyPlugin\Event\TierChanged;
use Setono\SyliusLoyaltyPlugin\Event\TierChanging;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\TierInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Repository\TierRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Tier\QualificationBasis\TierQualificationBasisRegistryInterface;
use Sylius\Component\Core\Model\ChannelInterface;

final class TierEvaluator implements TierEvaluatorInterface
{
    public function __construct(
        private readonly TierRepositoryInterface $tierRepository,
        private readonly TierQualificationBasisRegistryInterface $basisRegistry,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly EvaluationWindowResolverInterface $windowResolver,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Inline evaluation after a qualifying earn: upgrades apply immediately in the same
     * transaction; downgrades never happen here (the nightly reconcile handles them).
     */
    public function evaluate(LoyaltyAccountInterface $account): void
    {
        $qualified = $this->qualifiedTier($account);
        if (null === $qualified) {
            return;
        }

        $current = $account->getTier();
        if (null !== $current && $qualified->getPosition() <= $current->getPosition()) {
            return;
        }

        $this->change($account, $qualified);
        $account->setTierBelowThresholdSince(null);
    }

    /**
     * Nightly reconciliation: upgrades apply, downgrades apply immediately or after the
     * program's grace period, counted from when the account first evaluated below its tier.
     */
    public function reconcile(LoyaltyAccountInterface $account, \DateTimeImmutable $now): void
    {
        $qualified = $this->qualifiedTier($account);
        $current = $account->getTier();

        $qualifiedPosition = $qualified?->getPosition() ?? \PHP_INT_MIN;
        $currentPosition = null === $current ? \PHP_INT_MIN : $current->getPosition();

        if ($qualifiedPosition === $currentPosition) {
            $account->setTierBelowThresholdSince(null);

            return;
        }

        if ($qualifiedPosition > $currentPosition) {
            $this->change($account, $qualified);
            $account->setTierBelowThresholdSince(null);

            return;
        }

        // Below the current tier: start or continue the grace clock
        $channel = $account->getChannel();
        \assert($channel instanceof ChannelInterface);
        $graceDays = $this->programProvider->getByChannel($channel)->getTierDowngradeGraceDays();

        $belowSince = $account->getTierBelowThresholdSince();
        if (null === $belowSince) {
            $account->setTierBelowThresholdSince($now);
            $belowSince = $now;
        }

        if ($belowSince->modify(sprintf('+%d days', $graceDays)) <= $now) {
            $this->change($account, $qualified);
            $account->setTierBelowThresholdSince(null);
        }
    }

    /**
     * The highest enabled tier whose threshold the account meets on that tier's own basis.
     */
    private function qualifiedTier(LoyaltyAccountInterface $account): ?TierInterface
    {
        $channel = $account->getChannel();
        if (!$channel instanceof ChannelInterface) {
            return null;
        }

        $tiers = $this->tierRepository->findQualifiable($channel);
        if ([] === $tiers) {
            return null;
        }

        $window = $this->windowResolver->resolve($this->programProvider->getByChannel($channel));

        /** @var array<string, int> $metrics */
        $metrics = [];
        foreach ($tiers as $tier) {
            $basisCode = $tier->getQualificationBasis();
            $metrics[$basisCode] ??= $this->basisRegistry->get($basisCode)->calculate($account, $window);

            if ($metrics[$basisCode] >= $tier->getThreshold()) {
                return $tier;
            }
        }

        return null;
    }

    private function change(LoyaltyAccountInterface $account, ?TierInterface $to): void
    {
        $from = $account->getTier();

        $changing = new TierChanging($account, $from, $to);
        $this->eventDispatcher->dispatch($changing);
        if ($changing->isCancelled()) {
            return;
        }

        $account->setTier($to);

        $this->eventDispatcher->dispatch(new TierChanged($account, $from, $to));
    }
}
