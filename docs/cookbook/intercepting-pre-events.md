# Intercepting ledger writes with pre-events

Right before writing a ledger entry, the ledger dispatches a pre-event — inside the account
lock, in the same database transaction. Listeners can adjust or cancel the pending write. The
events are plain objects dispatched by class name (`Setono\SyliusLoyaltyPlugin\Event\...`):

| Event | Adjustable | `cancel()` means |
|---|---|---|
| `AwardingPoints` | `setPoints()`, `setExpiresAt()` | No credit is written |
| `RedeemingPoints` | nothing (the debit must equal the applied points) | Checkout completion aborts with a validation error |
| `ExpiringPoints` | nothing (points equal the lot's replay-derived remaining) | The lot is deferred and re-selected on the next expiry run |
| `ClawingBackPoints` | `setPoints()` | No clawback debit is written |

`AwardingPoints` covers both earning paths: `getOrder()` is set for order earning (and
`getSourceIdentifier()` is null), while action-trigger earning sets `getSourceIdentifier()`
(and `getOrder()` is null). All four events expose `getAccount()`.

## Example: double points for staff

```php
<?php

declare(strict_types=1);

namespace App\Loyalty\EventListener;

use Setono\SyliusLoyaltyPlugin\Event\AwardingPoints;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final class StaffDoublePointsListener
{
    public function __invoke(AwardingPoints $event): void
    {
        $email = $event->getAccount()->getCustomer()?->getEmail();
        if (null === $email || !str_ends_with($email, '@my-company.com')) {
            return;
        }

        $event->setPoints($event->getPoints() * 2);
    }
}
```

No configuration needed: `#[AsEventListener]` is autoconfigured by Symfony, and the events are
dispatched with the standard event dispatcher (an `EventSubscriberInterface` subscribing to
`AwardingPoints::class` works the same way).

## Example: a grace period before expiration

Cancelling `ExpiringPoints` writes nothing and leaves the lot open — it is simply re-selected
on the next `setono:sylius-loyalty:expire-points` run, which makes it the hook for
project-level grace logic:

```php
<?php

declare(strict_types=1);

namespace App\Loyalty\EventListener;

use Setono\SyliusLoyaltyPlugin\Event\ExpiringPoints;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final class ExpiryGracePeriodListener
{
    public function __invoke(ExpiringPoints $event): void
    {
        $expiresAt = $event->getLot()->getExpiresAt();

        // Give every lot 14 extra days beyond its nominal expiry
        if (null !== $expiresAt && $expiresAt->modify('+14 days') > new \DateTimeImmutable()) {
            $event->cancel();
        }
    }
}
```

Notes:

- Setting `AwardingPoints` points to `0` (or below) has the same effect as `cancel()`.
- Cancelling `RedeemingPoints` is a hard stop: the ledger throws a `LedgerConflictException`
  and the checkout `complete` transition is aborted — use it for last-second fraud checks, not
  for soft adjustments.
- Listeners run inside the ledger transaction: keep them fast and side-effect free. For
  reacting *after* a write committed, use the post-commit events instead (`PointsEarned`,
  `PointsRedeemed`, `PointsExpired`, `PointsClawedBack`, `RedemptionRolledBack`,
  `ManualAdjustment`).
