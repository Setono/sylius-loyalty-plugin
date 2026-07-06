<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Form\Type;

use Setono\SyliusLoyaltyPlugin\Tier\QualificationBasis\TierQualificationBasisRegistryInterface;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<\Setono\SyliusLoyaltyPlugin\Model\TierInterface>
 */
final class TierType extends AbstractType
{
    public function __construct(
        private readonly TierQualificationBasisRegistryInterface $basisRegistry,
        private readonly string $dataClass,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $basisChoices = [];
        $unitLabels = [];
        foreach ($this->basisRegistry->all() as $basis) {
            $basisChoices[$basis->getLabel()] = $basis->getCode();
            $unitLabels[$basis->getCode()] = $basis->getUnitLabel();
        }

        $builder
            ->add('code', TextType::class, [
                'label' => 'sylius.ui.code',
            ])
            ->add('name', TextType::class, [
                'label' => 'sylius.ui.name',
            ])
            ->add('channel', ChannelChoiceType::class, [
                'label' => 'sylius.ui.channel',
            ])
            ->add('position', IntegerType::class, [
                'label' => 'sylius.ui.position',
                'help' => 'setono_sylius_loyalty.form.tier.position_help',
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'sylius.ui.enabled',
                'required' => false,
            ])
            ->add('qualificationBasis', ChoiceType::class, [
                'label' => 'setono_sylius_loyalty.form.tier.qualification_basis',
                'choices' => $basisChoices,
                'choice_attr' => fn (string $code): array => ['data-unit-label' => $unitLabels[$code] ?? ''],
            ])
            ->add('threshold', IntegerType::class, [
                'label' => 'setono_sylius_loyalty.form.tier.threshold',
                'help' => 'setono_sylius_loyalty.form.tier.threshold_help',
            ])
            ->add('earningMultiplier', NumberType::class, [
                'label' => 'setono_sylius_loyalty.form.tier.earning_multiplier',
                'help' => 'setono_sylius_loyalty.form.tier.earning_multiplier_help',
                'scale' => 2,
            ])
            ->add('color', ColorType::class, [
                'label' => 'setono_sylius_loyalty.form.tier.color',
                'required' => false,
            ])
            ->add('translations', ResourceTranslationsType::class, [
                'label' => 'setono_sylius_loyalty.form.tier.benefits',
                'entry_type' => TierTranslationType::class,
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
        return 'setono_sylius_loyalty_tier';
    }
}
