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

> **Status:** under active development; no release is tagged yet. Detailed installation instructions
> (order entity extension, schema diffing, cron commands) are added as the relevant features land.

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
