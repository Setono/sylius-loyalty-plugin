# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

`setono/sylius-loyalty-plugin` adds a **loyalty program** to a Sylius 1.14 store. It is built around
an **append-only points ledger** — points are a financial liability, so balances are *derived* from
the ledger and never hand-edited — and delivered in three phases:

1. **Points core + rule engine** — earning (order & action triggers), a flexible earning-rule engine
   (structured conditions/amounts plus a sandboxed expression mode), redemption at checkout, expiry,
   and clawback.
2. **Tiers** — admin-created tiers with pluggable qualification bases, progress indicators, and earn
   hints.
3. **Referrals** — referral links, qualification, rewards, and extensible fraud checks.

The plugin integrates natively with Sylius (ResourceBundle CRUD, GridBundle admin listings, the
adjustment system for redemption, state-machine callbacks, and template events for shop UI) and is
channel-aware: a customer has one loyalty account per channel. Every meaningful action is an
extension point — tagged services composed with `setono/composite-compiler-pass`, dispatched events,
and overridable resources.

> This file is the authoritative project reference: the original design spec is intentionally **not**
> committed to the repository. Keep it current as features land.

### Domain glossary

- **Ledger** — the append-only list of `LoyaltyTransaction` rows (Doctrine single-table inheritance).
  Rows are never updated or deleted; corrections are new compensating entries.
- **Lot** — a credit transaction with an optional `expiresAt`. Points are consumed FIFO from lots.
- **Replay** — re-deriving per-lot remaining balances by processing an account's ledger in order
  (`LotReplayer`). Cheap because ledgers are per-account and small; cached balances mean balance
  reads never replay.
- **Clawback** — a debit that reverses points previously earned for an order that was later cancelled
  or refunded.
- **Requested vs applied points** — at checkout the customer records an intent to spend N points
  (`loyaltyPointsRequested` on the order); the amount actually debited (`appliedPoints`) is clamped to
  `min(requested, balance, cap)` on every order recalculation.

## Code Standards

Follow clean code principles and SOLID design patterns when working with this codebase:
- Write clean, readable, and maintainable code
- Apply SOLID principles (Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion)
- Use meaningful variable and method names
- Keep methods and classes focused on a single responsibility
- Favor composition over inheritance
- Write code that is easy to test and extend
- **Prefer public properties over getters/setters for plain data holders** (DTOs, events, value carriers). Reserve getters/setters for entities and classes with real invariants or behaviour. E.g. an event like `AwardingPoints` exposes `public int $points` rather than `getPoints()`/`setPoints()`.
- **Inject an optional logger with the `LoggerAwareInterface` + `NullLogger` pattern**, not a nullable constructor argument: implement `Psr\Log\LoggerAwareInterface`, `use LoggerAwareTrait`, default `$this->logger` to a `NullLogger` in the constructor, and wire it with a `setLogger` call whose `logger` argument is `on-invalid="ignore"`.

### Testing Requirements
- Write unit tests for all new functionality (if it makes sense)
- Follow the BDD-style naming convention for test methods (e.g., `it_should_do_something_when_condition_is_met`)
- **MUST use Prophecy for mocking** - Use the `ProphecyTrait` and `$this->prophesize()` for all mocks, NOT PHPUnit's `$this->createMock()`
- **Form testing** - Use Symfony's best practices for form testing as documented at https://symfony.com/doc/current/form/unit_testing.html
  - Extend `Symfony\Component\Form\Test\TypeTestCase` for form type tests
  - Use `$this->factory->create()` to create form instances
  - Test form submission, validation, and data transformation
- Ensure tests are isolated and don't depend on external state
- Test both happy path and edge cases

### UI Verification
- **All UI changes MUST be verified using the Playwright MCP** - After making any change that affects the rendered UI (templates, forms, styling, layout, flash messages, etc.), use the Playwright MCP to navigate the running test application and confirm the change renders and behaves as expected
- Run the test application (see [Test Application](#test-application)) and use the Playwright MCP `browser_navigate`, `browser_snapshot`, and `browser_take_screenshot` tools to inspect the affected pages
- Verify both the visual result and the interactive behavior (e.g. submitting forms, triggering flash messages)

## Development Commands

Based on the `composer.json` scripts section:

### Code Quality & Testing
- `composer analyse` - Run PHPStan static analysis (level max)
- `composer check-style` - Check code style with ECS (Easy Coding Standard)
- `composer fix-style` - Fix code style issues automatically with ECS
- `composer phpunit` - Run PHPUnit tests

### Static Analysis

#### PHPStan Configuration
PHPStan is configured in `phpstan.neon` with:
- **Analysis Level**: max (strictest)
- **Extensions**: Auto-loaded via `phpstan/extension-installer`
  - `phpstan/phpstan-symfony` - Symfony framework integration
  - `phpstan/phpstan-doctrine` - Doctrine ORM integration
  - `phpstan/phpstan-phpunit` - PHPUnit test integration
  - `jangregor/phpstan-prophecy` - Prophecy mocking integration
- **Symfony Integration**: Uses console application loader (`tests/PHPStan/console_application.php`)
- **Doctrine Integration**: Uses object manager loader (`tests/PHPStan/object_manager.php`)
- **Exclusions**: Test application directory (`tests/Application/*`)
- **Baseline**: Generate with `composer analyse -- --generate-baseline` to track improvements

### Test Application
The plugin includes a test Symfony application in `tests/Application/` for development and testing:
- Navigate to `tests/Application/` directory
- Run `yarn install && yarn build` to build assets
- Use standard Symfony commands for the test app
- **Sylius Backend Credentials**: Username: `sylius`, Password: `sylius`

## Bash Tools Recommendations

Use the right tool for the right job when executing bash commands:

- **Finding FILES?** → Use `fd` (fast file finder)
- **Finding TEXT/strings?** → Use `rg` (ripgrep for text search)
- **Finding CODE STRUCTURE?** → Use `ast-grep` (syntax-aware code search)
- **SELECTING from multiple results?** → Pipe to `fzf` (interactive fuzzy finder)
- **Interacting with JSON?** → Use `jq` (JSON processor)
- **Interacting with YAML or XML?** → Use `yq` (YAML/XML processor)

Examples:
- `fd "*.php" | fzf` - Find PHP files and interactively select one
- `rg "function.*validate" | fzf` - Search for validation functions and select
- `ast-grep --lang php -p 'class $name extends $parent'` - Find class inheritance patterns

## Architecture Overview

### Translations
All customer- and admin-facing strings are translatable via `src/Resources/translations/`. Translation
keys are added alongside the feature that introduces them; English is authored first and is
authoritative, and the full 16-locale set (`en`, `da`, `sv`, `no`, `fi`, `de`, `fr`, `es`, `it`, `nl`,
`pl`, `pt`, `cs`, `hu`, `ro`, `uk`) is filled in as part of the Phase 1 close-out. Generated (non-English)
locale files carry a header comment marking them machine-translated pending native review.

- **Translation Domains**:
  - `messages.*` - general UI labels (`setono_sylius_loyalty.ui.*`) and form labels
    (`setono_sylius_loyalty.form.*`)
  - `flashes.*` - flash messages (success/error)
