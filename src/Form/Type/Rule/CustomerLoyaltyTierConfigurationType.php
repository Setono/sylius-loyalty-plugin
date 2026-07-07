<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Form\Type\Rule;

use Setono\SyliusLoyaltyPlugin\Model\TierInterface;
use Setono\SyliusLoyaltyPlugin\Repository\TierRepositoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array{tier: string}>
 */
final class CustomerLoyaltyTierConfigurationType extends AbstractType
{
    public function __construct(
        private readonly TierRepositoryInterface $tierRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];
        /** @var TierInterface $tier */
        foreach ($this->tierRepository->findAll() as $tier) {
            $label = sprintf('%s (%s)', (string) $tier->getName(), (string) $tier->getChannel()?->getCode());
            $choices[$label] = $tier->getCode();
        }

        $builder->add('tier', ChoiceType::class, [
            'label' => 'setono_sylius_loyalty.form.promotion_rule.minimum_tier',
            'choices' => $choices,
            'constraints' => [new NotBlank(['groups' => ['sylius']])],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_loyalty_promotion_rule_customer_loyalty_tier';
    }
}
