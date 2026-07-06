<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\DependencyInjection;

use Setono\SyliusLoyaltyPlugin\Doctrine\ORM\EarningRuleRepository;
use Setono\SyliusLoyaltyPlugin\Doctrine\ORM\LoyaltyAccountRepository;
use Setono\SyliusLoyaltyPlugin\Doctrine\ORM\LoyaltyProgramRepository;
use Setono\SyliusLoyaltyPlugin\Doctrine\ORM\LoyaltyTransactionRepository;
use Setono\SyliusLoyaltyPlugin\Doctrine\ORM\TierRepository;
use Setono\SyliusLoyaltyPlugin\Form\Type\EarningRuleConditionType;
use Setono\SyliusLoyaltyPlugin\Form\Type\EarningRuleType;
use Setono\SyliusLoyaltyPlugin\Form\Type\LoyaltyProgramType;
use Setono\SyliusLoyaltyPlugin\Form\Type\TierType;
use Setono\SyliusLoyaltyPlugin\Model\DryRunResult;
use Setono\SyliusLoyaltyPlugin\Model\DryRunResultInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarningRule;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleCondition;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleConditionInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgram;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\Tier;
use Setono\SyliusLoyaltyPlugin\Model\TierInterface;
use Setono\SyliusLoyaltyPlugin\Model\TierTranslation;
use Setono\SyliusLoyaltyPlugin\Model\TierTranslationInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('setono_sylius_loyalty');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('manual_adjustment_reasons')
                    ->info('Reason codes selectable when an admin manually adjusts a loyalty account. Labels resolve via the translation key "setono_sylius_loyalty.ui.manual_reason.<code>"')
                    ->defaultValue(['goodwill', 'correction', 'promotion', 'other'])
                    ->scalarPrototype()->cannotBeEmpty()->end()
                ->end()
                ->arrayNode('triggers')
                    ->info('Earning trigger event classes. Each class must extend Setono\SyliusLoyaltyPlugin\Event\Trigger\EarningTriggerEvent')
                    ->defaultValue([])
                    ->scalarPrototype()->cannotBeEmpty()->end()
                ->end()
                ->arrayNode('transaction_types')
                    ->info('Custom loyalty transaction types added to the Doctrine discriminator map: discriminator value => transaction class')
                    ->useAttributeAsKey('type')
                    ->defaultValue([])
                    ->scalarPrototype()->cannotBeEmpty()->end()
                ->end()
                ->booleanNode('retain_anonymized_ledger')
                    ->info('On customer deletion, keep de-identified ledger rows (type, points, dates, channel) linked to an opaque account token for accounting continuity, instead of deleting everything (the default)')
                    ->defaultFalse()
                ->end()
                ->arrayNode('expression_editor')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('cdn_base_url')
                            ->info('Base URL for the version-pinned ESM imports (CodeMirror) used by the admin expression editor. Point it at a self-hosted copy for intranet or strict-CSP setups')
                            ->defaultValue('https://esm.sh')
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        $this->addResourcesSection($rootNode);

        return $treeBuilder;
    }

    private function addResourcesSection(ArrayNodeDefinition $rootNode): void
    {
        /** @var NodeBuilder $resources */
        $resources = $rootNode
            ->children()
                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
        ;

        $this->addResourceNode($resources, 'account', LoyaltyAccount::class, LoyaltyAccountInterface::class, LoyaltyAccountRepository::class);
        $this->addResourceNode($resources, 'program', LoyaltyProgram::class, LoyaltyProgramInterface::class, LoyaltyProgramRepository::class, LoyaltyProgramType::class);
        $this->addResourceNode($resources, 'transaction', LoyaltyTransaction::class, LoyaltyTransactionInterface::class, LoyaltyTransactionRepository::class);
        $this->addResourceNode($resources, 'earning_rule', EarningRule::class, EarningRuleInterface::class, EarningRuleRepository::class, EarningRuleType::class);
        $this->addResourceNode($resources, 'earning_rule_condition', EarningRuleCondition::class, EarningRuleConditionInterface::class, null, EarningRuleConditionType::class);
        $this->addResourceNode($resources, 'dry_run_result', DryRunResult::class, DryRunResultInterface::class);
        $this->addResourceNode($resources, 'tier', Tier::class, TierInterface::class, TierRepository::class, TierType::class, [
            'model' => TierTranslation::class,
            'interface' => TierTranslationInterface::class,
        ]);
    }

    /**
     * @param class-string $model
     * @param class-string $interface
     * @param class-string|null $repository
     * @param class-string|null $form
     * @param array{model: class-string, interface: class-string}|null $translation
     */
    private function addResourceNode(
        NodeBuilder $resources,
        string $name,
        string $model,
        string $interface,
        ?string $repository = null,
        ?string $form = null,
        ?array $translation = null,
    ): void {
        $resourceNode = $resources->arrayNode($name)->addDefaultsIfNotSet();
        $resourceChildren = $resourceNode->children();
        $resourceChildren->variableNode('options');

        $classes = $resourceChildren
            ->arrayNode('classes')
                ->addDefaultsIfNotSet()
                ->children()
        ;

        $classes->scalarNode('model')->defaultValue($model)->cannotBeEmpty();
        $classes->scalarNode('interface')->defaultValue($interface)->cannotBeEmpty();
        $classes->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty();
        $classes->scalarNode('factory')->defaultValue(Factory::class)->cannotBeEmpty();

        $formNode = $classes->scalarNode('form')->cannotBeEmpty();
        if (null !== $form) {
            $formNode->defaultValue($form);
        }

        $repositoryNode = $classes->scalarNode('repository')->cannotBeEmpty();
        if (null !== $repository) {
            $repositoryNode->defaultValue($repository);
        }

        if (null !== $translation) {
            $translationClasses = $resourceChildren
                ->arrayNode('translation')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->variableNode('options')->end()
                        ->arrayNode('classes')
                            ->addDefaultsIfNotSet()
                            ->children()
            ;

            $translationClasses->scalarNode('model')->defaultValue($translation['model'])->cannotBeEmpty();
            $translationClasses->scalarNode('interface')->defaultValue($translation['interface'])->cannotBeEmpty();
            $translationClasses->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty();
            $translationClasses->scalarNode('factory')->defaultValue(Factory::class)->cannotBeEmpty();
            $translationClasses->scalarNode('repository')->cannotBeEmpty();
            $translationClasses->scalarNode('form')->cannotBeEmpty();
        }
    }
}
