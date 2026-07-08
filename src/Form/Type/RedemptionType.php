<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Form\Type;

use Setono\SyliusLoyaltyPlugin\Model\OrderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<OrderInterface>
 */
final class RedemptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('loyaltyPointsRequested', IntegerType::class, [
            'label' => 'setono_sylius_loyalty.form.redemption.points',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderInterface::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_loyalty_redemption';
    }
}
