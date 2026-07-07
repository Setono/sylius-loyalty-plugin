<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Form\Type;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The per-channel program settings. Amount-based settings are entered in minor units of the
 * channel's base currency (Sylius processes all order amounts in the base currency); the
 * earning rate is deliberately not here — it is defined by earning rules.
 *
 * @extends AbstractType<\Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface>
 */
final class LoyaltyProgramType extends AbstractType
{
    public function __construct(
        private readonly string $dataClass,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('awardOrderPointsAt', ChoiceType::class, [
                'label' => 'setono_sylius_loyalty.form.program.award_order_points_at',
                'help' => 'setono_sylius_loyalty.form.program.award_order_points_at_help',
                'choices' => [
                    'setono_sylius_loyalty.form.program.award_moment_payment_paid' => LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_PAYMENT_PAID,
                    'setono_sylius_loyalty.form.program.award_moment_order_fulfilled' => LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_ORDER_FULFILLED,
                ],
            ])
            ->add('earningBasis', ChoiceType::class, [
                'label' => 'setono_sylius_loyalty.form.program.earning_basis',
                'choices' => [
                    'setono_sylius_loyalty.form.program.earning_basis_items_total' => LoyaltyProgramInterface::EARNING_BASIS_ITEMS_TOTAL,
                    'setono_sylius_loyalty.form.program.earning_basis_order_total' => LoyaltyProgramInterface::EARNING_BASIS_ORDER_TOTAL,
                ],
                'help' => 'setono_sylius_loyalty.form.program.earning_basis_help',
            ])
            ->add('includeTaxes', CheckboxType::class, [
                'label' => 'setono_sylius_loyalty.form.program.include_taxes',
                'required' => false,
            ])
            ->add('rounding', ChoiceType::class, [
                'label' => 'setono_sylius_loyalty.form.program.rounding',
                'choices' => [
                    'setono_sylius_loyalty.form.program.rounding_floor' => LoyaltyProgramInterface::ROUNDING_FLOOR,
                    'setono_sylius_loyalty.form.program.rounding_round' => LoyaltyProgramInterface::ROUNDING_ROUND,
                    'setono_sylius_loyalty.form.program.rounding_ceil' => LoyaltyProgramInterface::ROUNDING_CEIL,
                ],
            ])
            ->add('redemptionConversionPoints', IntegerType::class, [
                'label' => 'setono_sylius_loyalty.form.program.redemption_conversion_points',
                'help' => 'setono_sylius_loyalty.form.program.redemption_conversion_help',
            ])
            ->add('redemptionConversionAmount', IntegerType::class, [
                'label' => 'setono_sylius_loyalty.form.program.redemption_conversion_amount',
                'help' => 'setono_sylius_loyalty.form.program.base_currency_help',
            ])
            ->add('minRedeemPoints', IntegerType::class, [
                'label' => 'setono_sylius_loyalty.form.program.min_redeem_points',
            ])
            ->add('maxRedeemPercentOfOrder', IntegerType::class, [
                'label' => 'setono_sylius_loyalty.form.program.max_redeem_percent_of_order',
                'help' => 'setono_sylius_loyalty.form.program.max_redeem_percent_of_order_help',
            ])
            ->add('pointsExpiryDays', IntegerType::class, [
                'label' => 'setono_sylius_loyalty.form.program.points_expiry_days',
                'help' => 'setono_sylius_loyalty.form.program.points_expiry_days_help',
                'required' => false,
            ])
            ->add('clawbackPolicy', ChoiceType::class, [
                'label' => 'setono_sylius_loyalty.form.program.clawback_policy',
                'choices' => [
                    'setono_sylius_loyalty.form.program.clawback_policy_allow_negative' => LoyaltyProgramInterface::CLAWBACK_POLICY_ALLOW_NEGATIVE,
                    'setono_sylius_loyalty.form.program.clawback_policy_clamp_to_zero' => LoyaltyProgramInterface::CLAWBACK_POLICY_CLAMP_TO_ZERO,
                ],
            ])
            ->add('retroactiveGuestPoints', CheckboxType::class, [
                'label' => 'setono_sylius_loyalty.form.program.retroactive_guest_points',
                'help' => 'setono_sylius_loyalty.form.program.retroactive_guest_points_help',
                'required' => false,
            ])
            ->add('tierEvaluationWindow', ChoiceType::class, [
                'label' => 'setono_sylius_loyalty.form.program.tier_evaluation_window',
                'choices' => [
                    'setono_sylius_loyalty.form.program.tier_window_calendar_year' => LoyaltyProgramInterface::TIER_EVALUATION_WINDOW_CALENDAR_YEAR,
                    'setono_sylius_loyalty.form.program.tier_window_rolling_12_months' => LoyaltyProgramInterface::TIER_EVALUATION_WINDOW_ROLLING_12_MONTHS,
                    'setono_sylius_loyalty.form.program.tier_window_lifetime' => LoyaltyProgramInterface::TIER_EVALUATION_WINDOW_LIFETIME,
                ],
            ])
            ->add('tierDowngradeGraceDays', IntegerType::class, [
                'label' => 'setono_sylius_loyalty.form.program.tier_downgrade_grace_days',
                'help' => 'setono_sylius_loyalty.form.program.tier_downgrade_grace_days_help',
            ])
            ->add('showEarnableOnProduct', CheckboxType::class, [
                'label' => 'setono_sylius_loyalty.form.program.show_earnable_on_product',
                'required' => false,
            ])
            ->add('showEarnableInCart', CheckboxType::class, [
                'label' => 'setono_sylius_loyalty.form.program.show_earnable_in_cart',
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
        return 'setono_sylius_loyalty_program';
    }
}
