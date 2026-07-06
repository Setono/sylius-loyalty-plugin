<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Fixture\Factory;

use Setono\SyliusLoyaltyPlugin\Model\EarningRuleConditionInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

/**
 * Public API — reusable in host projects' test apps.
 */
final class EarningRuleExampleFactory implements ExampleFactoryInterface
{
    private readonly OptionsResolver $optionsResolver;

    /**
     * @param FactoryInterface<EarningRuleInterface> $ruleFactory
     * @param FactoryInterface<EarningRuleConditionInterface> $conditionFactory
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(
        private readonly FactoryInterface $ruleFactory,
        private readonly FactoryInterface $conditionFactory,
        private readonly ChannelRepositoryInterface $channelRepository,
    ) {
        $this->optionsResolver = new OptionsResolver();

        $this->optionsResolver
            ->setRequired('name')
            ->setAllowedTypes('name', 'string')
            ->setRequired('channel')
            ->setAllowedTypes('channel', 'string')
            ->setNormalizer('channel', function (Options $options, string $code): ChannelInterface {
                $channel = $this->channelRepository->findOneByCode($code);
                Assert::isInstanceOf($channel, ChannelInterface::class, sprintf('Channel "%s" not found', $code));

                return $channel;
            })
            ->setDefault('trigger', EarningRuleInterface::TRIGGER_ORDER_ELIGIBLE)
            ->setDefault('scope', EarningRuleInterface::SCOPE_ORDER)
            ->setDefault('scope_configuration', [])
            ->setDefault('conditions_match', EarningRuleInterface::CONDITIONS_MATCH_ALL)
            ->setDefault('conditions', [])
            ->setAllowedTypes('conditions', 'array')
            ->setRequired('amount_type')
            ->setAllowedTypes('amount_type', 'string')
            ->setDefault('amount_configuration', [])
            ->setDefault('priority', 0)
            ->setDefault('stackable', true)
            ->setDefault('enabled', true)
            ->setDefault('dry_run', false)
        ;
    }

    /**
     * @param array<array-key, mixed> $options
     */
    public function create(array $options = []): EarningRuleInterface
    {
        /** @var array{name: string, channel: ChannelInterface, trigger: string, scope: string, scope_configuration: array<string, mixed>, conditions_match: string, conditions: list<array{type: string, configuration: array<string, mixed>}>, amount_type: string, amount_configuration: array<string, mixed>, priority: int, stackable: bool, enabled: bool, dry_run: bool} $options */
        $options = $this->optionsResolver->resolve($options);

        $rule = $this->ruleFactory->createNew();
        $rule->setName($options['name']);
        $rule->setChannel($options['channel']);
        $rule->setTrigger($options['trigger']);
        $rule->setScope($options['scope']);
        $rule->setScopeConfiguration($options['scope_configuration']);
        $rule->setConditionsMatch($options['conditions_match']);
        $rule->setAmountType($options['amount_type']);
        $rule->setAmountConfiguration($options['amount_configuration']);
        $rule->setPriority($options['priority']);
        $rule->setStackable($options['stackable']);
        $rule->setEnabled($options['enabled']);
        $rule->setDryRun($options['dry_run']);

        foreach ($options['conditions'] as $conditionOptions) {
            $condition = $this->conditionFactory->createNew();
            $condition->setType($conditionOptions['type']);
            $condition->setConfiguration($conditionOptions['configuration'] ?? []);
            $rule->addCondition($condition);
        }

        return $rule;
    }
}
