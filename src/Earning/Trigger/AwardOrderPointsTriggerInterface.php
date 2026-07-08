<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Earning\Trigger;

use Sylius\Component\Core\Model\OrderInterface;

interface AwardOrderPointsTriggerInterface
{
    /**
     * Dispatches the AwardOrderPoints command for the order if the given moment is the channel program's
     * configured award moment. A no-op otherwise, so the same order transition can be observed from
     * several state-machine hooks safely.
     *
     * @param string $moment one of the LoyaltyProgram AWARD_ORDER_POINTS_AT_* values
     */
    public function trigger(OrderInterface $order, string $moment): void;
}
