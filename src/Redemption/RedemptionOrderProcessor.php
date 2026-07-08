<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Redemption;

use Setono\SyliusLoyaltyPlugin\Model\OrderInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Order\Model\OrderInterface as BaseOrderInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class RedemptionOrderProcessor implements OrderProcessorInterface
{
    public const ADJUSTMENT_TYPE = 'setono_sylius_loyalty_redemption';

    /**
     * @param FactoryInterface<AdjustmentInterface> $adjustmentFactory
     */
    public function __construct(
        private readonly LoyaltyAccountProviderInterface $accountProvider,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly RedemptionCalculatorInterface $calculator,
        private readonly FactoryInterface $adjustmentFactory,
    ) {
    }

    public function process(BaseOrderInterface $order): void
    {
        if (!$order instanceof OrderInterface) {
            return;
        }

        // Recomputed from scratch on every order recalculation.
        $order->removeAdjustmentsRecursively(self::ADJUSTMENT_TYPE);

        $requested = $order->getLoyaltyPointsRequested();
        if ($requested <= 0) {
            return;
        }

        $customer = $order->getCustomer();
        $channel = $order->getChannel();
        if (!$customer instanceof CustomerInterface || null === $channel) {
            return;
        }

        $account = $this->accountProvider->getAccount($customer, $channel);
        if (!$account->isEnabled()) {
            return;
        }

        $program = $this->programProvider->getProgram($channel);
        $applied = $this->calculator->calculate($requested, $account->getBalance(), $order->getItemsTotal(), $program);
        if ($applied <= 0) {
            return;
        }

        $amount = $this->calculator->amount($applied, $program);
        if ($amount <= 0) {
            return;
        }

        $adjustment = $this->adjustmentFactory->createNew();
        $adjustment->setType(self::ADJUSTMENT_TYPE);
        $adjustment->setLabel('Loyalty points redemption');
        $adjustment->setAmount(-$amount);
        $adjustment->setNeutral(false);
        $adjustment->setDetails(['points' => $applied]);

        $order->addAdjustment($adjustment);
    }
}
