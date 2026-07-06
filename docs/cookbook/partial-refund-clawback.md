# Clawing back points on a partial refund

Full clawback ships built in: when an order that earned points is cancelled, or its payment
goes through the `refund` transition, the plugin debits the earned points automatically.
**Partial** refunds are project-specific (Sylius core has no partial-refund flow), so the
ledger exposes the write as a public extension point:

```php
// Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface
public function clawback(OrderInterface $order, int $points): ?ClawbackLoyaltyTransactionInterface;
```

`$points` is a positive magnitude (the ledger applies the sign). The earn credit is looked up
via the order; if the order never earned points, the call is a no-op returning `null`.

## Example: proportional clawback from a refund event

A listener on your refund mechanism's event (Sylius Refund Plugin, a PSP webhook, a custom
admin action — anything that knows the order and the refunded amount):

```php
<?php

declare(strict_types=1);

namespace App\Loyalty\EventListener;

use App\Refund\Event\UnitsRefunded; // hypothetical: exposes getOrder() and getRefundedAmount()
use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final class ClawbackOnPartialRefundListener
{
    public function __construct(
        private readonly LoyaltyLedgerInterface $ledger,
        private readonly LoyaltyTransactionRepositoryInterface $transactionRepository,
    ) {
    }

    public function __invoke(UnitsRefunded $event): void
    {
        $order = $event->getOrder();

        $earn = $this->transactionRepository->findEarnOrderTransaction($order);
        if (null === $earn || $order->getTotal() <= 0) {
            return; // the order never earned points
        }

        // Claw back the earned points proportionally to the refunded share of the order
        $points = (int) round($earn->getPoints() * $event->getRefundedAmount() / $order->getTotal());
        if ($points <= 0) {
            return;
        }

        $this->ledger->clawback($order, $points);
    }
}
```

Both `LoyaltyLedgerInterface` and `LoyaltyTransactionRepositoryInterface` are aliased to their
interface ids, so autowiring works.

## What the ledger does with it

- **Pre-event** — `Setono\SyliusLoyaltyPlugin\Event\ClawingBackPoints` is dispatched first;
  listeners may adjust the points or cancel the write (then `clawback()` returns `null`).
- **Clawback policy (clamp)** — with the program's default `clamp_to_zero` policy, the debit is
  reduced at write time to `min(points, balance)` so the balance lands at exactly zero and
  never goes negative — the ledger entry records what was actually debited. With the
  `allow_negative` policy the full amount is debited and the balance may go negative.
- **Idempotency** — a database unique constraint guarantees each earn credit is clawed back
  **at most once**. Repeating the call for the same order (event redelivery, concurrent
  processes) is a silent no-op returning `null`.

That last point has a design consequence: an order supports **one** clawback entry, ever. If
your flow can refund the same order in several steps, aggregate the refunded amounts and call
`clawback()` once with the final proportional total — a second call will not write anything.
For the same reason, once your partial clawback is written, the built-in full clawback on a
later cancellation/refund of that order becomes a no-op (and vice versa).
