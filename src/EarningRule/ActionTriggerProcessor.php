<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EarningRule;

use Psr\Log\LoggerInterface;
use Setono\SyliusLoyaltyPlugin\Event\Trigger\EarningTriggerEvent;
use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Repository\EarningRuleRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Resolver\TriggerChannelResolverInterface;

final class ActionTriggerProcessor implements ActionTriggerProcessorInterface
{
    public function __construct(
        private readonly TriggerChannelResolverInterface $channelResolver,
        private readonly LoyaltyAccountProviderInterface $accountProvider,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly EarningRuleRepositoryInterface $ruleRepository,
        private readonly EarningRuleEvaluatorInterface $evaluator,
        private readonly DryRunLoggerInterface $dryRunLogger,
        private readonly LoyaltyLedgerInterface $ledger,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function process(EarningTriggerEvent $event): ?EarnActionLoyaltyTransactionInterface
    {
        $channel = $this->channelResolver->resolve($event);
        if (null === $channel) {
            // An expected data situation (e.g. a new customer in a multi-channel shop), not a bug
            $this->logger->warning(sprintf(
                '[Loyalty] No channel could be resolved for the "%s" trigger (customer: %s); nothing was awarded',
                $event::getTriggerCode(),
                (string) $event->getCustomer()->getEmail(),
            ));

            return null;
        }

        $rules = $this->ruleRepository->findForEvaluation($channel, $event::getTriggerCode());
        if ([] === $rules) {
            return null;
        }

        $account = $this->accountProvider->getByCustomerAndChannel($event->getCustomer(), $channel);
        $program = $this->programProvider->getByChannel($channel);

        $context = new EarningContext(
            channel: $channel,
            customer: $event->getCustomer(),
            account: $account,
            context: $this->contextVariables($event),
        );

        $result = $this->evaluator->evaluate($rules, $context, $program);

        $this->dryRunLogger->log($result, $account);

        if ($result->points <= 0) {
            return null;
        }

        return $this->ledger->earnAction(
            $account,
            $result->points,
            $event->getSourceIdentifier(),
            $result->rulesBreakdown,
            self::expiresAt($program),
        );
    }

    /**
     * The subclass's own public properties are the trigger's typed expression context.
     *
     * @return array<string, mixed>
     */
    private function contextVariables(EarningTriggerEvent $event): array
    {
        $variables = [];
        foreach ((new \ReflectionClass($event))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (EarningTriggerEvent::class === $property->getDeclaringClass()->getName()) {
                continue;
            }

            /** @var mixed $value */
            $value = $property->getValue($event);
            $variables[$property->getName()] = $value;
        }

        return $variables;
    }

    private static function expiresAt(LoyaltyProgramInterface $program): ?\DateTimeImmutable
    {
        $days = $program->getPointsExpiryDays();

        return null === $days ? null : new \DateTimeImmutable(sprintf('+%d days', $days));
    }
}
