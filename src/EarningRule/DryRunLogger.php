<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule;

use Doctrine\Persistence\ManagerRegistry;
use Setono\SyliusLoyaltyPlugin\Model\DryRunResultInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class DryRunLogger implements DryRunLoggerInterface
{
    /**
     * @param FactoryInterface<DryRunResultInterface> $dryRunResultFactory
     */
    public function __construct(
        private readonly FactoryInterface $dryRunResultFactory,
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    public function log(
        EvaluationResult $result,
        ?LoyaltyAccountInterface $account = null,
        ?OrderInterface $order = null,
    ): void {
        if ([] === $result->dryRunEvaluations) {
            return;
        }

        $manager = null;
        foreach ($result->dryRunEvaluations as $evaluation) {
            if (!$evaluation->matched) {
                continue;
            }

            $dryRunResult = $this->dryRunResultFactory->createNew();
            Assert::isInstanceOf($dryRunResult, DryRunResultInterface::class);

            $dryRunResult->setRule($evaluation->rule);
            $dryRunResult->setAccount($account);
            $dryRunResult->setOrder($order);
            $dryRunResult->setPoints((int) round($evaluation->points));
            $dryRunResult->setDetails([
                'claimedBasis' => $evaluation->claimedBasis,
                'claimedUnits' => $evaluation->claimedUnits,
                'factor' => $evaluation->factor,
                'failedConditions' => $evaluation->failedConditions,
            ]);

            $manager ??= $this->managerRegistry->getManagerForClass($dryRunResult::class);
            Assert::notNull($manager);
            $manager->persist($dryRunResult);
        }

        $manager?->flush();
    }
}
