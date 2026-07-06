<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event\Trigger;

final class CustomerRegisteredTriggerEvent extends EarningTriggerEvent
{
    public static function getTriggerCode(): string
    {
        return 'customer_registered';
    }

    public static function getLabel(): string
    {
        return 'setono_sylius_loyalty.trigger.customer_registered';
    }
}
