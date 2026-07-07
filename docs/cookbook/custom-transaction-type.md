# Adding a custom transaction type

The ledger is a Doctrine single-table-inheritance hierarchy rooted at
`Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransaction` (table
`setono_sylius_loyalty__transaction`, discriminator column `type`, length 64). You can add your
own transaction types — e.g. an imported balance from a legacy program — without touching the
plugin's mapping.

## 1. The entity

Extend `Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransaction` (credits carry an optional
`expiresAt` and act as lots) or `Setono\SyliusLoyaltyPlugin\Model\DebitLoyaltyTransaction`:

```php
<?php

declare(strict_types=1);

namespace App\Entity\Loyalty;

use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransaction;

class LegacyImportLoyaltyTransaction extends CreditLoyaltyTransaction
{
    protected ?string $legacyReference = null;

    public static function getDiscriminator(): string
    {
        return 'legacy_import';
    }

    public function getLegacyReference(): ?string
    {
        return $this->legacyReference;
    }

    public function setLegacyReference(?string $legacyReference): void
    {
        $this->legacyReference = $legacyReference;
    }
}
```

## 2. The Doctrine mapping (host-side)

The plugin maps the parent; you map your subclass. Because the hierarchy is single-table, every
extra column must be nullable:

```xml
<!-- config/doctrine/LegacyImportLoyaltyTransaction.orm.xml -->
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                                      http://doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="App\Entity\Loyalty\LegacyImportLoyaltyTransaction">
        <field name="legacyReference" column="legacy_reference" type="string" length="255" nullable="true"/>
    </entity>

</doctrine-mapping>
```

(Attribute mapping works just as well if that is what your project uses.) Generate a migration
with `doctrine:migrations:diff` — the plugin ships no migrations.

## 3. Register the class as a resource

```yaml
# config/packages/sylius_resource.yaml
sylius_resource:
    resources:
        app.legacy_import_loyalty_transaction:
            classes:
                model: App\Entity\Loyalty\LegacyImportLoyaltyTransaction
```

The plugin scans the registered resources at metadata load time and adds every model
extending the transaction root to the discriminator map under its own
`getDiscriminator()` value, so `legacy_import` becomes a valid `type` value alongside the
built-ins (`earn_order`, `earn_action`, `earn_referral`, `redeem`, `redeem_rollback`,
`expire`, `clawback`, `manual_credit`, `manual_debit`).

## All writes still go through the ledger

The ledger is **append-only** and `Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface` is
its single write path: every write locks the account row (pessimistic write lock inside a
transaction), appends the entry, and updates the cached `balance`/`lifetimeEarned` in the same
transaction. Never `persist()` a transaction row directly, and never update or delete one —
corrections are new compensating entries.

To actually write your custom type, follow the same pattern the default ledger uses — typically
by decorating `LoyaltyLedgerInterface` (its service id is the interface FQCN, so re-alias or
use Symfony service decoration) with a method for your type:

```php
$this->entityManager->wrapInTransaction(function () use ($accountId, $points): void {
    $account = $this->entityManager
        ->getRepository($this->accountClass)
        ->find($accountId, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);

    $transaction = new LegacyImportLoyaltyTransaction();
    $transaction->setAccount($account);
    $transaction->setPoints($points); // credits positive, debits negative
    $this->entityManager->persist($transaction);

    $account->setBalance($account->getBalance() + $points);
    $account->setLifetimeEarned($account->getLifetimeEarned() + $points);
});
```

Custom rows participate in replay like any other: credits with an `expiresAt` are lots consumed
FIFO, and `setono:sylius-loyalty:verify-ledger` checks them against the same invariants.
