<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Form\Extension;

use Sylius\Bundle\OrderBundle\Form\Type\CartType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Adds the "spend points" field to the cart form, mirroring how Sylius' own CartTypeExtension adds
 * the promotion coupon field. The field maps onto the order's loyaltyPointsRequested and is submitted
 * with the cart-save flow, so the redemption adjustment recalculates like any other cart change — no
 * dedicated controller or route needed.
 */
final class CartTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('loyaltyPointsRequested', IntegerType::class, [
            'label' => 'setono_sylius_loyalty.form.redemption.points',
            'required' => false,
            'empty_data' => '0',
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [CartType::class];
    }
}
