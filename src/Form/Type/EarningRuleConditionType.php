<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Form\Type;

use Setono\SyliusLoyaltyPlugin\EarningRule\Checker\ConditionCheckerRegistryInterface;
use Setono\SyliusLoyaltyPlugin\EarningRule\Checker\ExpressionConditionChecker;
use Setono\SyliusLoyaltyPlugin\Exception\InvalidExpressionException;
use Setono\SyliusLoyaltyPlugin\Expression\ExpressionValidatorInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleConditionInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<\Setono\SyliusLoyaltyPlugin\Model\EarningRuleConditionInterface>
 */
final class EarningRuleConditionType extends AbstractType
{
    public function __construct(
        private readonly ConditionCheckerRegistryInterface $conditionCheckers,
        private readonly ExpressionValidatorInterface $expressionValidator,
        private readonly string $dataClass,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];
        foreach ($this->conditionCheckers->all() as $checker) {
            $choices[$checker->getLabel()] = $checker->getType();
        }

        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'setono_sylius_loyalty.form.earning_rule.condition_type',
                'choices' => $choices,
            ])
            ->add('configuration', JsonType::class, [
                'label' => 'setono_sylius_loyalty.form.earning_rule.condition_configuration',
                'required' => false,
                'help' => 'setono_sylius_loyalty.form.earning_rule.condition_configuration_help',
            ])
            ->add('expression', ExpressionType::class, [
                'label' => 'setono_sylius_loyalty.form.earning_rule.condition_expression',
                'help' => 'setono_sylius_loyalty.form.earning_rule.condition_expression_help',
                'mapped' => false,
            ])
        ;

        $builder->addEventListener(FormEvents::POST_SET_DATA, self::populateExpression(...));
        $builder->addEventListener(FormEvents::POST_SUBMIT, $this->handleSubmittedCondition(...));
    }

    private static function populateExpression(FormEvent $event): void
    {
        $condition = $event->getData();
        if (!$condition instanceof EarningRuleConditionInterface || ExpressionConditionChecker::TYPE !== $condition->getType()) {
            return;
        }

        $expression = $condition->getConfiguration()['expression'] ?? null;
        if (is_string($expression)) {
            $event->getForm()->get('expression')->setData($expression);
        }
    }

    /**
     * Expression conditions are validated on save with the same parse + whitelist as the lint
     * endpoint, narrowed to the rule's trigger when reachable.
     */
    private function handleSubmittedCondition(FormEvent $event): void
    {
        $condition = $event->getData();
        if (!$condition instanceof EarningRuleConditionInterface || ExpressionConditionChecker::TYPE !== $condition->getType()) {
            return;
        }

        $form = $event->getForm();

        $expression = $form->get('expression')->getData();
        if (!is_string($expression) || '' === trim($expression)) {
            $form->get('expression')->addError(new FormError('setono_sylius_loyalty.form.earning_rule.expression_required'));

            return;
        }

        try {
            $this->expressionValidator->validate($expression, $condition->getRule()?->getTrigger());
            $condition->setConfiguration(['expression' => $expression]);
        } catch (InvalidExpressionException $e) {
            $form->get('expression')->addError(new FormError($e->getMessage()));
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
        return 'setono_sylius_loyalty_earning_rule_condition';
    }
}
