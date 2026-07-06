# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

`setono/sylius-loyalty-plugin` is a loyalty plugin for Sylius 1.14 (PHP >= 8.1, Symfony ^6.4, Doctrine ORM, MySQL 8). It provides:

- **Points** (Phase 1): event-driven earning on order/payment lifecycle and customer actions, redemption at checkout as a distributed discount adjustment, expiration, and clawback on cancellation/refund — all backed by a strictly **append-only ledger**.
- **Tiers** (Phase 2): admin-created tiers with configurable qualification bases, earning multipliers, a tier-gated promotion rule checker, and shop progress indicators.
- **Referrals** (Phase 3): referral codes with URL/cookie attribution, first-order qualification, and extensible fraud checks.

Guiding principles: points are a financial liability, so the ledger is the source of truth and balances are derived caches; all earning is idempotent by construction (DB-level unique constraints); everything is channel-aware; every meaningful action dispatches an event; extension points are tagged services or config-registered event classes. B2C only — no cashback, badges/challenges/leaderboards, B2B accounts, emails, or API Platform endpoints.

## Domain Glossary

- **Loyalty account**: one per (customer, channel). Holds a cached `balance` and `lifetimeEarned` — both derived from the ledger, never hand-edited.
- **Ledger**: the append-only list of `LoyaltyTransaction` rows (Doctrine single-table inheritance). Rows are never updated or deleted; corrections are new compensating entries.
- **Lot**: a credit transaction with an optional `expiresAt`. There is no stored "remaining" column — per-lot remainders are **derived** by replay.
- **Replay**: deterministic processing of an account's ledger in `occurredAt ASC, id ASC` order; debits consume open lots FIFO in consumption order `expiresAt ASC NULLS LAST, occurredAt ASC, id ASC`.
- **Clawback**: a debit written when an order that earned points is cancelled/refunded, referencing the original earn credit.
- **Requested vs applied points**: `order.loyaltyPointsRequested` is the customer's persisted intent; on every order recalculation the applied amount is derived as `min(requested, balance, cap)` clamped to a clean multiple of the conversion ratio. The checkout debit is always the **applied** amount.
- **Dry run**: an earning rule mode that evaluates against live traffic and logs would-be awards to an audit table instead of writing ledger entries.

## Code Standards

Follow clean code principles and SOLID design patterns when working with this codebase:
- Write clean, readable, and maintainable code
- Apply SOLID principles (Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion)
- Use meaningful variable and method names
- Keep methods and classes focused on a single responsibility
- Favor composition over inheritance
- Write code that is easy to test and extend

Plugin-specific conventions:
- **Services** are declared in XML under `src/Resources/config/services/`, loaded from `services.xml`. Service ids are FQCNs; every service with an interface gets an interface **alias**, and consumers are wired against the interface id so projects can swap implementations by re-aliasing. No autowiring inside the plugin — explicit `<argument type="service" id="…"/>` wiring.
- **API surface**: all classes `final` except entities; interfaces are the only supported extension contracts; anything not documented as an extension point is `@internal`.
- **Models** live in `src/Model/` as interface + default implementation, registered under the `setono_sylius_loyalty.resources` config node; Doctrine mappings are XML in `src/Resources/config/doctrine/model/`. Only Doctrine ORM is supported.
- **Database tables** are prefixed `setono_sylius_loyalty__`.
- **No Doctrine migrations ship** — the XML mappings are the schema's source of truth; host projects generate their own via `doctrine:migrations:diff`.
- All ledger writes go through the `LoyaltyLedger` service (pessimistic locking via Doctrine's `LockMode::PESSIMISTIC_WRITE` inside `wrapInTransaction()`); never write transaction rows directly.

### Testing Requirements

Policy: **all code is unit tested; behavior that touches the framework or database is additionally functionally tested; everything with a UI is covered by Playwright.** The levels complement — never substitute for — each other.

- **Unit tests** (`tests/Unit/`, plus `tests/DependencyInjection/`): baseline for every class. BDD-style method names (e.g. `it_awards_points_once_for_a_paid_order`).
- **MUST use Prophecy for mocking** - Use the `ProphecyTrait` and `$this->prophesize()` for all mocks, NOT PHPUnit's `$this->createMock()`
- **Form testing** - Extend `Symfony\Component\Form\Test\TypeTestCase`, use `$this->factory->create()`, test submission, validation, and data transformation (see https://symfony.com/doc/current/form/unit_testing.html)
- **Functional tests** (`tests/Functional/`): run against the Sylius test app in `tests/Application/` with MySQL. Extend `Setono\SyliusLoyaltyPlugin\Tests\Functional\FunctionalTestCase`; database state is isolated per test by dama/doctrine-test-bundle. Tests that need real commits (cross-process locking/concurrency) opt out via `StaticDriver` and clean up after themselves.
- **E2E tests** (`e2e/`): the committed Playwright suite is the acceptance gate for all UI behavior.
- Ensure tests are isolated and don't depend on external state; test both happy path and edge cases.

### UI Verification
- **All UI changes MUST be verified using the Playwright MCP** - After making any change that affects the rendered UI (templates, forms, styling, layout, flash messages, etc.), use the Playwright MCP to navigate the running test application and confirm the change renders and behaves as expected
- Verify both the visual result and the interactive behavior (e.g. submitting forms, triggering flash messages), then lock the behavior in as a committed spec in `e2e/`.

## Development Commands

### Code Quality & Testing
- `composer analyse` - Run PHPStan static analysis (level max)
- `composer check-style` / `composer fix-style` - Check/fix code style with ECS
- `vendor/bin/phpunit --testsuite unit` - Run unit tests (no database needed)
- `vendor/bin/phpunit --testsuite functional` - Run functional tests (needs the test app database, `APP_ENV=test`)
- `vendor/bin/rector --dry-run` - Rector check (CI runs this)
- `vendor/bin/infection` - Mutation tests (scoped to the unit suite; MSI thresholds are unset until the plugin is feature-complete)
- `(cd e2e && npx playwright test)` - Run the e2e suite (needs the test app served with seeded fixtures; the config boots `php -S` itself)

### PHPStan Configuration
PHPStan runs at **level max** over `src` and `tests` with the Symfony/Doctrine/PHPUnit/Prophecy extensions (auto-loaded via `phpstan/extension-installer`). Loaders live under `tests/PHPStan/` (`console_application.php`, `object_manager.php`); the test application directory is excluded.

### Test Application
The plugin includes a Sylius test application in `tests/Application/`:
- Use **Node 20** (`.nvmrc`; the Sylius UI toolchain is incompatible with Node 22): `nvm use && yarn install && yarn build`
- Database (MySQL/MariaDB on 127.0.0.1, see `.env`): `APP_ENV=test bin/console doctrine:database:create && APP_ENV=test bin/console doctrine:schema:create && APP_ENV=test bin/console sylius:fixtures:load default -n`
- Serve with `APP_ENV=test php -S 127.0.0.1:8080 -t public` (or `symfony serve`)
- **Sylius Backend Credentials**: Username: `sylius`, Password: `sylius`

### Console Commands (shipped by the plugin)
Documented here as they are implemented:
- (none yet — Phase 1 will add `setono:sylius-loyalty:expire-points`, `verify-ledger`, `recalculate-balances`, `inspect-account`, `export-customer-data`, `trigger-birthdays`, `prune-dry-run-results`)

## Extension Points

Documented here as they are implemented. Planned surface: earning condition types (`setono_sylius_loyalty.earning_condition`), amount types (`.earning_amount`), expression functions (`.expression_function`), referral fraud checks (`.referral_fraud_check`), tier qualification bases (`.tier_qualification_basis`), custom transaction types (`setono_sylius_loyalty.transaction_types` config + discriminator-map listener), and earning triggers (`setono_sylius_loyalty.triggers` config: event classes extending `EarningTriggerEvent`). Every extension interface is registered for autoconfiguration.

## Bash Tools Recommendations

Use the right tool for the right job when executing bash commands:

- **Finding FILES?** → Use `fd` (fast file finder)
- **Finding TEXT/strings?** → Use `rg` (ripgrep for text search)
- **Finding CODE STRUCTURE?** → Use `ast-grep` (syntax-aware code search)
- **SELECTING from multiple results?** → Pipe to `fzf` (interactive fuzzy finder)
- **Interacting with JSON?** → Use `jq` (JSON processor)
- **Interacting with YAML or XML?** → Use `yq` (YAML/XML processor)

## Architecture Overview

### Translations
Customer- and admin-facing strings are translatable via `src/Resources/translations/` (domains `messages`, `flashes`, `validators`). English is authored first and is authoritative; the shipped locale set is en, da, sv, no, fi, de, fr, es, it, nl, pl, pt, cs, hu, ro, uk. Generated locale files carry a header comment marking them machine-translated pending native review. Key namespaces: `setono_sylius_loyalty.ui.*`, `setono_sylius_loyalty.form.*`.

### Known deviations from the original specification
- The product-page earn hint renders via the `sylius.shop.product.show.add_to_cart_form` template event (just above the add-to-cart button) — Sylius 1.14 has no template event directly below the button.
- The order-payment `pay` transition lives on the `sylius_order_payment` state machine graph (not `sylius_payment`).
- Doctrine mappings live in `src/Resources/config/doctrine/model/` (the `AbstractResourceBundle` default).
