<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Provider\Shop;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Repository\TierRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Tier\EvaluationWindowResolverInterface;
use Setono\SyliusLoyaltyPlugin\Tier\QualificationBasis\TierQualificationBasisRegistryInterface;
use Sylius\Component\Core\Model\ChannelInterface;

final class TierProgressProvider implements TierProgressProviderInterface
{
    public function __construct(
        private readonly TierRepositoryInterface $tierRepository,
        private readonly TierQualificationBasisRegistryInterface $basisRegistry,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly EvaluationWindowResolverInterface $windowResolver,
    ) {
    }

    public function getProgress(LoyaltyAccountInterface $account): ?TierProgress
    {
        $channel = $account->getChannel();
        if (!$channel instanceof ChannelInterface) {
            return null;
        }

        $tiers = $this->tierRepository->findQualifiable($channel);
        if ([] === $tiers) {
            return null;
        }

        $current = $account->getTier();
        $currentPosition = null === $current ? \PHP_INT_MIN : $current->getPosition();

        // The next tier is the lowest-positioned tier above the current one
        $next = null;
        foreach ($tiers as $tier) {
            if ($tier->getPosition() > $currentPosition) {
                $next = $tier;
            } else {
                break;
            }
        }

        if (null === $next) {
            return new TierProgress($current, null, 0, 0, true);
        }

        $window = $this->windowResolver->resolve($this->programProvider->getByChannel($channel));
        $metric = $this->basisRegistry->get($next->getQualificationBasis())->calculate($account, $window);

        return new TierProgress($current, $next, $metric, $next->getThreshold(), false);
    }
}
