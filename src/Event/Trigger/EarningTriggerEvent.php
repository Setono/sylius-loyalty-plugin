<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event\Trigger;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

/**
 * The base class of every earning trigger. A trigger IS an event class: extend this class,
 * register it under "setono_sylius_loyalty.triggers" in the bundle config, and fire it with a
 * plain event dispatch — the plugin evaluates the trigger's earning rules and writes the
 * award.
 *
 * The subclass's own public readonly properties become the typed "context" variables available
 * to expressions and appear in the expression reference panel automatically.
 */
abstract class EarningTriggerEvent
{
    /**
     * @param ChannelInterface|null $channel resolved via the TriggerChannelResolver chain when
     *        null; an unresolvable channel makes the dispatch a logged no-op
     * @param string|null $sourceIdentifier deduplicates awards per account; defaults to the
     *        trigger code, which means "once per account, ever" — repeatable triggers pass
     *        their own (e.g. "birthday:2026", "review:123")
     */
    public function __construct(
        private readonly CustomerInterface $customer,
        private readonly ?ChannelInterface $channel = null,
        private readonly ?string $sourceIdentifier = null,
    ) {
    }

    abstract public static function getTriggerCode(): string;

    /**
     * A translation key shown in the rule form's trigger select.
     */
    abstract public static function getLabel(): string;

    public function getCustomer(): CustomerInterface
    {
        return $this->customer;
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function getSourceIdentifier(): string
    {
        return $this->sourceIdentifier ?? static::getTriggerCode();
    }
}
