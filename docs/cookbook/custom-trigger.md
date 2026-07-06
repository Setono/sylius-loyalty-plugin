# Adding a custom earning trigger

A trigger *is* an event class: extend
`Setono\SyliusLoyaltyPlugin\Event\Trigger\EarningTriggerEvent`, register the class under the
`setono_sylius_loyalty.triggers` config node, and fire it with a plain event dispatch. The
plugin evaluates the trigger's earning rules and writes the award — you never touch the ledger.

## 1. The event class

```php
<?php

declare(strict_types=1);

namespace App\Loyalty\Trigger;

use Setono\SyliusLoyaltyPlugin\Event\Trigger\EarningTriggerEvent;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

final class SocialShareTriggerEvent extends EarningTriggerEvent
{
    public function __construct(
        CustomerInterface $customer,
        public readonly string $network,
        ?ChannelInterface $channel = null,
    ) {
        // One award per customer per network; omit the third argument for "once per account, ever"
        parent::__construct($customer, $channel, sprintf('social_share:%s', $network));
    }

    public static function getTriggerCode(): string
    {
        return 'app_social_share';
    }

    public static function getLabel(): string
    {
        return 'app.trigger.social_share';
    }
}
```

The subclass's own public readonly properties (`network` here) become the trigger's **typed
expression context**: rule expressions can reference `network`, and the property is listed with
its type in the expression editor's reference panel automatically.

## 2. Config registration

```yaml
# config/packages/setono_sylius_loyalty.yaml
setono_sylius_loyalty:
    triggers:
        - App\Loyalty\Trigger\SocialShareTriggerEvent
```

A compiler pass validates the class (must extend `EarningTriggerEvent`, non-abstract, unique
trigger code) and wires the plugin's handler to it. The trigger then appears in the admin
earning rule form's trigger select under your `app.trigger.social_share` label.

## 3. Dispatching

Fire it with the standard event dispatcher, from wherever the action happens:

```php
use Psr\EventDispatcher\EventDispatcherInterface;

$this->eventDispatcher->dispatch(new SocialShareTriggerEvent($customer, 'instagram'));
```

## Source identifier semantics

`sourceIdentifier` deduplicates awards per account at the database level (a unique constraint —
concurrent dispatches and message redeliveries are no-ops by construction):

- **Default** (pass `null` / omit): the source identifier is the trigger code, which means
  **once per account, ever**. Right for one-shot actions like "completed profile".
- **Repeatable triggers pass their own**: the built-ins use `review:<id>` and
  `birthday:<year>`; the example above uses `social_share:<network>`. A dispatch with an
  already-awarded identifier is a silent no-op, so re-dispatching is always safe.

## Channel

Pass the channel when you know it. When `null`, the plugin resolves it via the
`TriggerChannelResolver` chain (re-alias
`Setono\SyliusLoyaltyPlugin\Resolver\TriggerChannelResolverInterface` to customize); an
unresolvable channel makes the dispatch a logged no-op.
