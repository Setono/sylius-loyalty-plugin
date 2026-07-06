<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Form\Type;

use Setono\SyliusLoyaltyPlugin\EarningRule\Amount\AmountCalculatorRegistryInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<\Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface>
 */
final class EarningRuleType extends AbstractType
{
    /**
     * @param array<string, array{class: class-string, label: string, context: array<string, string>}> $triggerCatalog
     */
    public function __construct(
        private readonly AmountCalculatorRegistryInterface $amountCalculators,
        private readonly array $triggerCatalog,
        private readonly string $dataClass,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $triggerChoices = ['setono_sylius_loyalty.trigger.order_eligible' => EarningRuleInterface::TRIGGER_ORDER_ELIGIBLE];
        foreach ($this->triggerCatalog as $code => $trigger) {
            $triggerChoices[$trigger['label']] = $code;
        }

        $amountChoices = [];
        foreach ($this->amountCalculators->all() as $calculator) {
            $amountChoices[$calculator->getLabel()] = $calculator->getType();
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'sylius.ui.name',
            ])
            ->add('channel', ChannelChoiceType::class, [
                'label' => 'sylius.ui.channel',
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'sylius.ui.enabled',
                'required' => false,
            ])
            ->add('dryRun', CheckboxType::class, [
                'label' => 'setono_sylius_loyalty.form.earning_rule.dry_run',
                'help' => 'setono_sylius_loyalty.form.earning_rule.dry_run_help',
                'required' => false,
            ])
            ->add('priority', IntegerType::class, [
                'label' => 'sylius.ui.priority',
            ])
            ->add('trigger', ChoiceType::class, [
                'label' => 'setono_sylius_loyalty.form.earning_rule.trigger',
                'choices' => $triggerChoices,
            ])
            ->add('scope', ChoiceType::class, [
                'label' => 'setono_sylius_loyalty.form.earning_rule.scope',
                'help' => 'setono_sylius_loyalty.form.earning_rule.scope_help',
                'choices' => [
                    'setono_sylius_loyalty.form.earning_rule.scope_order' => EarningRuleInterface::SCOPE_ORDER,
                    'setono_sylius_loyalty.form.earning_rule.scope_taxon' => EarningRuleInterface::SCOPE_TAXON,
                    'setono_sylius_loyalty.form.earning_rule.scope_product' => EarningRuleInterface::SCOPE_PRODUCT,
                ],
            ])
            ->add('scopeConfiguration', JsonType::class, [
                'label' => 'setono_sylius_loyalty.form.earning_rule.scope_configuration',
                'help' => 'setono_sylius_loyalty.form.earning_rule.scope_configuration_help',
                'required' => false,
            ])
            ->add('conditionsMatch', ChoiceType::class, [
                'label' => 'setono_sylius_loyalty.form.earning_rule.conditions_match',
                'choices' => [
                    'setono_sylius_loyalty.form.earning_rule.conditions_match_all' => EarningRuleInterface::CONDITIONS_MATCH_ALL,
                    'setono_sylius_loyalty.form.earning_rule.conditions_match_any' => EarningRuleInterface::CONDITIONS_MATCH_ANY,
                ],
            ])
            ->add('conditions', CollectionType::class, [
                'label' => 'setono_sylius_loyalty.form.earning_rule.conditions',
                'entry_type' => EarningRuleConditionType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'button_add_label' => 'setono_sylius_loyalty.form.earning_rule.add_condition',
            ])
            ->add('amountType', ChoiceType::class, [
                'label' => 'setono_sylius_loyalty.form.earning_rule.amount_type',
                'choices' => $amountChoices,
            ])
            ->add('amountConfiguration', JsonType::class, [
                'label' => 'setono_sylius_loyalty.form.earning_rule.amount_configuration',
                'help' => 'setono_sylius_loyalty.form.earning_rule.amount_configuration_help',
                'required' => false,
            ])
            ->add('startsAt', DateTimeType::class, [
                'label' => 'setono_sylius_loyalty.form.earning_rule.starts_at',
                'help' => 'setono_sylius_loyalty.form.earning_rule.window_timezone_help',
                'widget' => 'single_text',
                'required' => false,
                'input' => 'datetime_immutable',
            ])
            ->add('endsAt', DateTimeType::class, [
                'label' => 'setono_sylius_loyalty.form.earning_rule.ends_at',
                'help' => 'setono_sylius_loyalty.form.earning_rule.window_timezone_help',
                'widget' => 'single_text',
                'required' => false,
                'input' => 'datetime_immutable',
            ])
            ->add('stackable', CheckboxType::class, [
                'label' => 'setono_sylius_loyalty.form.earning_rule.stackable',
                'help' => 'setono_sylius_loyalty.form.earning_rule.stackable_help',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_loyalty_earning_rule';
    }
}
