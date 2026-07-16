# Setono SyliusLoyaltyPlugin

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]
[![Code Coverage][ico-code-coverage]][link-code-coverage]
[![Mutation testing][ico-infection]][link-infection]

A loyalty program for [Sylius](https://sylius.com) 1.14. The plugin is built around an **append-only
points ledger** (points are a financial liability, so balances are derived from the ledger and never
hand-edited) and is delivered in three phases: **points core + rule engine**, **tiers**, and
**referrals**. It integrates natively with Sylius — ResourceBundle CRUD, GridBundle admin listings,
the adjustment system for redemption, state-machine callbacks, and shop template events — and is
channel-aware (one loyalty account per customer per channel).

> **Status:** under active development; no release is tagged yet. Phase 1 (points core, rule engine,
> redemption, expiry, clawback, and the first admin screens) is landing incrementally.

## Installation

1. Require the plugin:
    ```bash
    composer require setono/sylius-loyalty-plugin
    ```

2. Register the bundle in `config/bundles.php`:
    ```php
    Setono\SyliusLoyaltyPlugin\SetonoSyliusLoyaltyPlugin::class => ['all' => true],
    ```

3. Import the routing in `config/routes/setono_sylius_loyalty.yaml`:
    ```yaml
    setono_sylius_loyalty:
        resource: "@SetonoSyliusLoyaltyPlugin/Resources/config/routes.yaml"
    ```
    This adds the admin screens (under `/admin/loyalty`) and the shop "My loyalty" page (under `/{_locale}`).

4. **Extend the order for redemption.** Redemption records the points a customer wants to spend on the
   order, so the order entity gets a `loyaltyPointsRequested` column. Add the trait and interface to your
   order class:
    ```php
    // src/Entity/Order/Order.php
    namespace App\Entity\Order;

    use Doctrine\ORM\Mapping as ORM;
    use Setono\SyliusLoyaltyPlugin\Model\OrderInterface as LoyaltyOrderInterface;
    use Setono\SyliusLoyaltyPlugin\Model\OrderTrait as LoyaltyOrderTrait;
    use Sylius\Component\Core\Model\Order as BaseOrder;

    #[ORM\Entity]
    #[ORM\Table(name: 'sylius_order')]
    class Order extends BaseOrder implements LoyaltyOrderInterface
    {
        use LoyaltyOrderTrait;
    }
    ```
    and point Sylius at it:
    ```yaml
    # config/packages/_sylius.yaml
    sylius_order:
        resources:
            order:
                classes:
                    model: App\Entity\Order\Order
    ```

5. **Update the database schema.** The plugin ships Doctrine mappings but no migrations, so generate one
   against your own schema and run it:
    ```bash
    bin/console doctrine:migrations:diff
    bin/console doctrine:migrations:migrate
    ```

## Console commands

Run these on a schedule (e.g. cron) to keep the ledger current:

| Command | What it does | Suggested cadence |
| --- | --- | --- |
| `setono:loyalty:expire-points` | Writes an expire row for every lot whose expiry has passed | daily |
| `setono:loyalty:award-birthday-points` | Awards the `customer_birthday` earning rules to customers whose birthday is today | daily |

## Admin

A single **Loyalty** entry under the **Marketing** menu opens the loyalty **dashboard** — account count,
outstanding points liability, and points earned/redeemed in the last 30 days — from which you navigate to:

- **Loyalty accounts** — a grid of accounts (customer, channel, balance, lifetime earned, status),
  filterable by customer, channel and status. Each row opens a **ledger inspector**: the account summary,
  the FIFO lot state, an invariant check that the cached balance matches the ledger replay, and the full
  ledger history.

## Contributing / local development

1. Run
    ```shell
    composer create-project --prefer-source --no-install --remove-vcs setono/sylius-loyalty-plugin:dev-master ProjectName
    ``` 
    or just click the `Use this template` button at the right corner of this repository.
2. Run
   ```shell
   cd ProjectName && composer install
   ```
3. From the plugin skeleton root directory, run the following commands:

    ```bash
    php init
    (cd tests/Application && yarn install)
    (cd tests/Application && yarn build)
    (cd tests/Application && bin/console assets:install)
    
    (cd tests/Application && bin/console doctrine:database:create)
    (cd tests/Application && bin/console doctrine:schema:create)
   
    (cd tests/Application && bin/console sylius:fixtures:load -n)
    ```
   
4. Start your local PHP server: `symfony serve` (see https://symfony.com/doc/current/setup/symfony_server.html for docs)

To be able to set up a plugin's database, remember to configure you database credentials in `tests/Application/.env` and `tests/Application/.env.test`.

[ico-version]: https://poser.pugx.org/setono/sylius-loyalty-plugin/v/stable
[ico-license]: https://poser.pugx.org/setono/sylius-loyalty-plugin/license
[ico-github-actions]: https://github.com/Setono/SyliusLoyaltyPlugin/workflows/build/badge.svg
[ico-code-coverage]: https://codecov.io/gh/Setono/SyliusLoyaltyPlugin/branch/master/graph/badge.svg
[ico-infection]: https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FSetono%2FSyliusLoyaltyPlugin%2Fmaster

[link-packagist]: https://packagist.org/packages/setono/sylius-loyalty-plugin
[link-github-actions]: https://github.com/Setono/SyliusLoyaltyPlugin/actions
[link-code-coverage]: https://codecov.io/gh/Setono/SyliusLoyaltyPlugin
[link-infection]: https://dashboard.stryker-mutator.io/reports/github.com/Setono/SyliusLoyaltyPlugin/master
