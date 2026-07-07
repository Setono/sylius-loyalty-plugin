# Rendering the balance in your templates

The plugin ships a shop account page and cart/checkout blocks, but the Twig functions behind
them are public, so you can render loyalty data anywhere (header badge, dashboard, emails):

| Function | Returns |
|---|---|
| `setono_sylius_loyalty_accounts(customer)` | The customer's loyalty accounts (one per channel) |
| `setono_sylius_loyalty_latest_transactions(account, limit = 25)` | The account's latest ledger entries, newest first |
| `setono_sylius_loyalty_transaction_type(transaction)` | The entry's discriminator value (e.g. `"earn_order"`, `"redeem"`) |
| `setono_sylius_loyalty_cart_redemption(cart)` | A `CartRedemptionView` for the cart's channel/customer (balance, presets, requested/applied points), or `null` |

## Example: a header points badge with recent activity

```twig
{# templates/bundles/SyliusShopBundle/_loyalty_badge.html.twig #}
{% if app.user is not null and app.user.customer is not null %}
    {% set accounts = setono_sylius_loyalty_accounts(app.user.customer)|filter(
        account => account.channel is same as(sylius.channel)
    ) %}

    {% for account in accounts %}
        <a href="{{ path('setono_sylius_loyalty_shop_account_loyalty') }}" class="item">
            <i class="star icon"></i>
            {{ account.balance }} {{ 'setono_sylius_loyalty.ui.points'|trans }}
        </a>

        <div class="ui popup">
            <ul>
                {% for transaction in setono_sylius_loyalty_latest_transactions(account, 5) %}
                    <li>
                        {{ transaction.occurredAt|date('Y-m-d') }}:
                        {{ transaction.points > 0 ? '+' : '' }}{{ transaction.points }}
                        ({{ setono_sylius_loyalty_transaction_type(transaction) }})
                    </li>
                {% endfor %}
            </ul>
        </div>
    {% endfor %}
{% endif %}
```

Accounts are per (customer, channel) — `setono_sylius_loyalty_accounts()` returns all of them,
so filter by the current channel as above. `account.balance` is the cached balance maintained
by the ledger; it is always safe to render directly.

## The account in PHP

Inject `Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface` (the interface is
the service alias, so autowiring works). `getByCustomerAndChannel()` creates the account lazily
on first access, so it never returns null:

```php
<?php

declare(strict_types=1);

namespace App\Loyalty;

use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\CustomerInterface;

final class LoyaltyBalance
{
    public function __construct(
        private readonly LoyaltyAccountProviderInterface $accountProvider,
        private readonly ChannelContextInterface $channelContext,
    ) {
    }

    public function get(CustomerInterface $customer): int
    {
        return $this->accountProvider
            ->getByCustomerAndChannel($customer, $this->channelContext->getChannel())
            ->getBalance();
    }
}
```

The account also exposes `getLifetimeEarned()` and `isEnabled()` — check the latter before
advertising earning or redemption to a customer whose account an admin has disabled.
