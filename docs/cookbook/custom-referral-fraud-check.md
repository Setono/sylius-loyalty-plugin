# Adding a custom referral fraud check

Referral fraud checks run at qualification, against the referee's qualifying order. A check is a
service implementing `Setono\SyliusLoyaltyPlugin\Referral\FraudCheck\ReferralFraudCheckInterface`:

```php
interface ReferralFraudCheckInterface
{
    public function check(ReferralInterface $referral, OrderInterface $order): ?FraudFlag;
}
```

Return `null` to pass. Return a `FraudFlag` to reject: **any** flag from **any** check rejects
the referral. All flags are stored on the referral (`ReferralInterface::getFraudFlags()`), so
admins can see exactly why a referral was rejected — and override the rejection if the flag
turns out to be a false positive.

`FraudFlag` carries a machine-readable check code and a human-readable detail:

```php
new FraudFlag('app_disposable_email', 'The referee registered with the disposable email domain "mailinator.com"')
```

Prefix your check codes (e.g. `app_`) so they cannot collide with the built-in ones.

## Example: reject disposable email domains

```php
<?php

declare(strict_types=1);

namespace App\Loyalty\FraudCheck;

use Setono\SyliusLoyaltyPlugin\Model\ReferralInterface;
use Setono\SyliusLoyaltyPlugin\Referral\FraudCheck\FraudFlag;
use Setono\SyliusLoyaltyPlugin\Referral\FraudCheck\ReferralFraudCheckInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class DisposableEmailDomainCheck implements ReferralFraudCheckInterface
{
    private const DISPOSABLE_DOMAINS = [
        '10minutemail.com',
        'guerrillamail.com',
        'mailinator.com',
        'yopmail.com',
    ];

    public function check(ReferralInterface $referral, OrderInterface $order): ?FraudFlag
    {
        $email = $referral->getRefereeCustomer()?->getEmailCanonical();
        if (null === $email || false === ($atPosition = strrpos($email, '@'))) {
            return null;
        }

        $domain = strtolower(substr($email, $atPosition + 1));
        if (in_array($domain, self::DISPOSABLE_DOMAINS, true)) {
            return new FraudFlag('app_disposable_email', sprintf('The referee registered with the disposable email domain "%s"', $domain));
        }

        return null;
    }
}
```

That is the whole integration: the plugin registers `ReferralFraudCheckInterface` for
autoconfiguration, so any service implementing it is tagged
`setono_sylius_loyalty.referral_fraud_check` automatically and runs alongside the shipped
checks on every qualification.

Only if your project has `autoconfigure: false` do you need to tag manually:

```yaml
# config/services.yaml
App\Loyalty\FraudCheck\DisposableEmailDomainCheck:
    tags: ['setono_sylius_loyalty.referral_fraud_check']
```

## Shipped checks

The plugin ships four checks: **self-referral** (same customer, identically normalized emails,
or a shared address between referrer and referee), **registration IP** (opt-in; flags a referee
registering from the same IP as another referee of the same referrer, using salted hashes purged
after 90 days), **account age** (the referee account existed before the referral was captured),
and **reward cap** (the referrer exceeded the rewarded-referrals cap for the trailing 30 days,
default 10).
