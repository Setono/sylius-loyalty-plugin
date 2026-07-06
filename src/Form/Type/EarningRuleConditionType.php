<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Form\Type;

use Setono\SyliusLoyaltyPlugin\EarningRule\Checker\ConditionCheckerRegistryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<\Setono\SyliusLoyaltyPlugin\Model\EarningRuleConditionInterface>
 */
final class EarningRuleConditionType extends AbstractType
{
    public function __construct(
        private readonly ConditionCheckerRegistryInterface $conditionCheckers,
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
            ->add('type', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'label' => 'setono_sylius_loyalty.form.earning_rule.condition_type',
                'choices' => $choices,
            ])
            ->add('configuration', JsonType::class, [
                'label' => 'setono_sylius_loyalty.form.earning_rule.condition_configuration',
                'required' => false,
                'help' => 'setono_sylius_loyalty.form.earning_rule.condition_configuration_help',
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
        return 'setono_sylius_loyalty_earning_rule_condition';
    }
}
