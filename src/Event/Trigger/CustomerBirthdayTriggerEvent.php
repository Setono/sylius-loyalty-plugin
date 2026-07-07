<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event\Trigger;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

/**
 * Fired once per calendar year per customer by the setono:sylius-loyalty:trigger-birthdays
 * command. Collecting the birthday is the installing project's concern — customers without a
 * birthday are never dispatched.
 */
final class CustomerBirthdayTriggerEvent extends EarningTriggerEvent
{
    public function __construct(
        CustomerInterface $customer,
        public readonly int $year,
        ?ChannelInterface $channel = null,
    ) {
        parent::__construct($customer, $channel, sprintf('birthday:%d', $year));
    }

    public static function getCode(): string
    {
        return 'customer_birthday';
    }

    public static function getLabel(): string
    {
        return 'setono_sylius_loyalty.trigger.customer_birthday';
    }
}
