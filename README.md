# Sylius Loyalty Plugin

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]
[![Code Coverage][ico-code-coverage]][link-code-coverage]
[![Mutation testing][ico-infection]][link-infection]

A loyalty program for [Sylius](https://sylius.com): **points** earned on orders and customer actions through a flexible
rule engine, **redeemed at checkout** as a real discount, with expiration, clawback, and a strictly append-only ledger
as the source of truth. Tiers (Phase 2) and referrals (Phase 3) build on the same core.

## Highlights

- **Append-only ledger** — points are treated as a financial liability. Every movement is a transaction row; balances
  are derived caches, verifiable with `setono:sylius-loyalty:verify-ledger`. Nothing is ever updated or deleted;
  corrections are compensating entries.
- **Rule engine** — earning rules with triggers (order, registration, review approval, birthday, or your own),
  conditions, scopes (order / taxon / product with exclusive item claiming), stacking, priorities, dry-run mode, and a
  sandboxed expression language with an admin code editor (autocompletion, inline linting, reference panel).
- **Checkout redemption** — customers apply points on the cart; the discount distributes to unit-level adjustments so
  taxes and promotions compute correctly. The customer's request is persisted and re-clamped on every order change —
  it grows back automatically when the cart allows it.
- **Idempotent by construction** — awarding is guarded by database unique constraints, so double event dispatches,
  message redeliveries, and concurrent processes can never double-credit or overspend (pessimistic locking on every
  ledger write).
- **Channel-aware** — one account and one program configuration per (customer, channel).
- **Operable** — console commands for expiry, ledger verification, balance recalculation, account inspection, and GDPR
  export; an admin ledger inspector with replay-derived lot states and invariant checks.

## Installation

```bash
composer require setono/sylius-loyalty-plugin
```

Register the plugin **before** `SyliusGridBundle` in `config/bundles.php`:

```php
<?php
return [
    // ...
    Setono\SyliusLoyaltyPlugin\SetonoSyliusLoyaltyPlugin::class => ['all' => true],
    Sylius\Bundle\GridBundle\SyliusGridBundle::class => ['all' => true],
    // ...
];
```

Import the routes in `config/routes/setono_sylius_loyalty.yaml`:

```yaml
setono_sylius_loyalty_admin:
    resource: '@SetonoSyliusLoyaltyPlugin/Resources/config/routes/admin.yaml'
    prefix: /admin

setono_sylius_loyalty_shop:
    resource: '@SetonoSyliusLoyaltyPlugin/Resources/config/routes/shop.yaml'
    prefix: /{_locale}
    requirements:
        _locale: ^[a-z]{2}(?:_[A-Z]{2})?$
```

If your shop does not use locale-prefixed URLs, import the shop routes without the `prefix`/`requirements` block.

### Extend your Order entity

The plugin persists the customer's redemption request on the order. Add the trait and interface to your `Order` entity:

```php
<?php

declare(strict_types=1);

namespace App\Entity\Order;

use Doctrine\ORM\Mapping as ORM;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyOrderInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyOrderTrait;
use Sylius\Component\Core\Model\Order as BaseOrder;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_order')]
class Order extends BaseOrder implements LoyaltyOrderInterface
{
    use LoyaltyOrderTrait;
}
```

### Create the schema

The plugin ships **no migrations** — the XML mappings are the schema's source of truth. Generate a migration in your
project and run it:

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

### Install assets

```bash
bin/console assets:install
```

## Configuration

Everything has sensible defaults. The full surface:

```yaml
setono_sylius_loyalty:
    # Reason codes for manual admin adjustments.
    # Labels resolve via setono_sylius_loyalty.ui.manual_reason.<code>
    manual_adjustment_reasons: [goodwill, correction, promotion, other]

    # Your own earning trigger event classes (see the cookbook)
    triggers: []

    # Custom ledger transaction types: discriminator value => class
    transaction_types: {}

    # On customer deletion, keep de-identified ledger rows linked to an opaque
    # token instead of deleting everything (accounting continuity)
    retain_anonymized_ledger: false

    referral:
        # The query parameter recognized as a referral code on any shop URL
        query_parameter: ref
        # Opt-in registration-IP fraud check (stores a salted hash, purged after 90 days)
        registration_ip_check: false
        ip_hash_salt: '%kernel.secret%'
        # Rewarded referrals per referrer per 30 days before the cap check flags
        reward_cap: 10

    expression_editor:
        # The admin expression editor loads CodeMirror as version-pinned ESM
        # imports from this base URL. Point it at a self-hosted copy for
        # intranet or strict-CSP environments.
        cdn_base_url: 'https://esm.sh'

    resources:
        # every model/repository/factory/form is swappable here
```

Per-channel program settings (conversion rate, minimum redemption, cap, expiry, clawback policy, award moment,
earning basis, rounding) live in the admin under **Marketing → Loyalty → Program**.

> **Note:** all point amounts convert against the **channel's base currency**; multi-currency channels convert at
> display time. Expiring stored value may be subject to consumer-protection law in some jurisdictions — check before
> enabling `points expire after (days)`.

## Cron jobs

| Command | Suggested schedule | Purpose |
|---|---|---|
| `setono:sylius-loyalty:expire-points` | daily, off-peak | writes `expire` debits for lots past their expiry |
| `setono:sylius-loyalty:trigger-birthdays` | daily | fires the `customer_birthday` trigger (idempotent per year) |
| `setono:sylius-loyalty:prune-dry-run-results --days=30` | daily | prunes the dry-run audit list |
| `setono:sylius-loyalty:evaluate-tiers` | nightly | tier reconciliation: downgrades after the grace period |
| `setono:sylius-loyalty:calculate-liability` | nightly, off-peak | snapshots the outstanding-liability dashboard widget |
| `setono:sylius-loyalty:expire-referrals` | daily | expires stale pending referrals, purges old IP hashes |
| `sylius:cancel-unpaid-orders` (Sylius core) | hourly | frees redemptions held by abandoned unpaid orders |

Operational commands (run on demand): `verify-ledger`, `recalculate-balances` (report-only by default),
`inspect-account <email> <channel>`, `export-customer-data <email>` (GDPR JSON).

## Extension points

Implementing the interface is the entire integration — every extension interface is autoconfigured. See the
[cookbook](docs/cookbook) for complete recipes:

- [Custom condition type](docs/cookbook/custom-condition-type.md)
- [Custom amount type](docs/cookbook/custom-amount-type.md)
- [Custom earning trigger](docs/cookbook/custom-trigger.md)
- [Custom expression function](docs/cookbook/custom-expression-function.md)
- [Custom transaction type](docs/cookbook/custom-transaction-type.md)
- [Intercepting points with pre-events](docs/cookbook/intercepting-pre-events.md)
- [Rendering the balance anywhere](docs/cookbook/rendering-the-balance.md)
- [Clawback on partial refunds](docs/cookbook/partial-refund-clawback.md)
- [Custom tier qualification basis](docs/cookbook/custom-tier-qualification-basis.md)

## Notes for specific setups

- **Birthday trigger**: Sylius collects birthdays only if your registration/profile forms do — the trigger is a silent
  no-op for customers without one.
- **Strict CSP / no CDN**: self-host the editor's ESM dependencies and set `expression_editor.cdn_base_url`.
- **Async awarding**: `AwardOrderPoints` and `ClaimPastOrderPoints` are dispatched through Messenger (sync by
  default). Route them to any transport — awarding is idempotent, so redeliveries are safe.

## Contributing

```bash
composer install
(cd tests/Application && yarn install && yarn build && bin/console doctrine:database:create doctrine:schema:create -e test)
composer analyse       # PHPStan, level max
composer check-style
vendor/bin/phpunit --testsuite unit
vendor/bin/phpunit --testsuite functional   # needs the test-app database
(cd e2e && npx playwright test)             # needs seeded fixtures: default + loyalty suites
```

[ico-version]: https://poser.pugx.org/setono/sylius-loyalty-plugin/v/stable
[ico-license]: https://poser.pugx.org/setono/sylius-loyalty-plugin/license
[ico-github-actions]: https://github.com/Setono/SyliusLoyaltyPlugin/workflows/build/badge.svg
[ico-code-coverage]: https://codecov.io/gh/Setono/SyliusLoyaltyPlugin/branch/master/graph/badge.svg
[ico-infection]: https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FSetono%2FSyliusLoyaltyPlugin%2Fmaster

[link-packagist]: https://packagist.org/packages/setono/sylius-loyalty-plugin
[link-github-actions]: https://github.com/Setono/SyliusLoyaltyPlugin/actions
[link-code-coverage]: https://codecov.io/gh/Setono/SyliusLoyaltyPlugin
[link-infection]: https://dashboard.stryker-mutator.io/reports/github.com/Setono/SyliusLoyaltyPlugin/master
