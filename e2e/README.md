# End-to-end tests

Playwright tests that drive the real shop/admin in a browser. Every UI detail in the plugin gets a
spec here.

## Prerequisites

The shop must be seeded. The specs use Sylius' `data-test-*` attributes, which only render in the
**test** env, so seed and serve the test env. From `tests/Application`:

```bash
APP_ENV=test bin/console doctrine:schema:create
APP_ENV=test bin/console sylius:fixtures:load -n            # channels, customers, products
APP_ENV=test bin/console sylius:fixtures:load setono_loyalty -n   # loyalty balances (see config/packages/setono_sylius_loyalty_fixtures.yaml)
yarn install && yarn build                                 # front-end assets
```

The `setono_loyalty` suite gives `shop@example.com` a loyalty balance via the
`LoyaltyBalanceFixture`, so the redemption widget has points to spend.

## Running

```bash
cd e2e
npm ci
npx playwright install chromium
npx playwright test
```

The Playwright config boots the shop itself (`symfony server:start` on port 8081). Set
`E2E_NO_SERVER=1` to run against a shop you're already running, and `E2E_BASE_URL` to point at a
different address.
