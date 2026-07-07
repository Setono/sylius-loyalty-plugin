<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Form\Type;

use Setono\SyliusLoyaltyPlugin\EarningRule\Amount\AmountCalculatorRegistryInterface;
use Setono\SyliusLoyaltyPlugin\EarningRule\Amount\ExpressionAmountCalculator;
use Setono\SyliusLoyaltyPlugin\EarningRule\Amount\MultiplierAmountCalculator;
use Setono\SyliusLoyaltyPlugin\EarningRule\Amount\PerAmountCalculator;
use Setono\SyliusLoyaltyPlugin\Exception\InvalidExpressionException;
use Setono\SyliusLoyaltyPlugin\Expression\ExpressionValidatorInterface;
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
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
        private readonly ExpressionValidatorInterface $expressionValidator,
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
            ->add('amountExpression', ExpressionType::class, [
                'label' => 'setono_sylius_loyalty.form.earning_rule.amount_expression',
                'help' => 'setono_sylius_loyalty.form.earning_rule.amount_expression_help',
                'mapped' => false,
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

        $builder->addEventListener(FormEvents::POST_SET_DATA, self::populateAmountExpression(...));
        $builder->addEventListener(FormEvents::POST_SUBMIT, $this->handleSubmittedRule(...));
    }

    /**
     * Expression mode stores the amount expression inside the amount configuration; the
     * dedicated editor field is populated from it.
     */
    private static function populateAmountExpression(FormEvent $event): void
    {
        $rule = $event->getData();
        if (!$rule instanceof EarningRuleInterface || ExpressionAmountCalculator::TYPE !== $rule->getAmountType()) {
            return;
        }

        $expression = $rule->getAmountConfiguration()['expression'] ?? null;
        if (is_string($expression)) {
            $event->getForm()->get('amountExpression')->setData($expression);
        }
    }

    /**
     * Validates expression mode on save (same parse + whitelist as the lint endpoint, narrowed
     * to the rule's trigger) and enforces the trigger-kind constraints: scope, per_amount, and
     * multiplier need an order and a basis, so they are only meaningful for the built-in order
     * trigger; multipliers are order-scoped only.
     */
    private function handleSubmittedRule(FormEvent $event): void
    {
        $rule = $event->getData();
        if (!$rule instanceof EarningRuleInterface) {
            return;
        }

        $form = $event->getForm();

        if (ExpressionAmountCalculator::TYPE === $rule->getAmountType()) {
            $expression = $form->get('amountExpression')->getData();
            if (!is_string($expression) || '' === trim($expression)) {
                $form->get('amountExpression')->addError(new FormError('setono_sylius_loyalty.form.earning_rule.expression_required'));
            } else {
                try {
                    $this->expressionValidator->validate($expression, $rule->getTrigger());
                    $rule->setAmountConfiguration(['expression' => $expression]);
                } catch (InvalidExpressionException $e) {
                    $form->get('amountExpression')->addError(new FormError($e->getMessage()));
                }
            }
        }

        $isOrderTrigger = EarningRuleInterface::TRIGGER_ORDER_ELIGIBLE === $rule->getTrigger();

        if (!$isOrderTrigger && EarningRuleInterface::SCOPE_ORDER !== $rule->getScope()) {
            $form->get('scope')->addError(new FormError('setono_sylius_loyalty.form.earning_rule.scope_requires_order_trigger'));
        }

        if (!$isOrderTrigger && in_array($rule->getAmountType(), [PerAmountCalculator::TYPE, MultiplierAmountCalculator::TYPE], true)) {
            $form->get('amountType')->addError(new FormError('setono_sylius_loyalty.form.earning_rule.amount_requires_order_trigger'));
        }

        if (MultiplierAmountCalculator::TYPE === $rule->getAmountType() && EarningRuleInterface::SCOPE_ORDER !== $rule->getScope()) {
            $form->get('amountType')->addError(new FormError('setono_sylius_loyalty.form.earning_rule.multiplier_is_order_scoped'));
        }
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
