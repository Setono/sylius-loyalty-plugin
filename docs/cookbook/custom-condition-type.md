# Adding a custom condition type

Earning rules are made of conditions. A condition type is a service implementing
`Setono\SyliusLoyaltyPlugin\EarningRule\Checker\ConditionCheckerInterface`:

```php
interface ConditionCheckerInterface
{
    public function getType(): string;

    /** A translation key for the rule form's condition type select. */
    public function getLabel(): string;

    /** @return class-string|null The form type rendering this condition's configuration, or null if it has none. */
    public function getConfigurationFormType(): ?string;

    /** @param array<string, mixed> $configuration */
    public function check(array $configuration, EarningContext $context): bool;
}
```

`check()` receives the condition's stored configuration and an
`Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext` exposing everything a rule may look at:
`$context->channel`, `$context->customer`, `$context->account`, `$context->order` (null for
action-trigger earning), `$context->itemAmounts` (order item id => eligible amount in minor
units), `$context->context` (typed trigger context variables), plus `$context->getNow()` and
`$context->getBasis()`. Use `getNow()` instead of `new \DateTimeImmutable()` so the admin rule
tester can preview scheduled rules.

## Example: newsletter subscribers only

```php
<?php

declare(strict_types=1);

namespace App\Loyalty\Checker;

use Setono\SyliusLoyaltyPlugin\EarningRule\Checker\ConditionCheckerInterface;
use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;

final class NewsletterSubscriberConditionChecker implements ConditionCheckerInterface
{
    public const TYPE = 'app_newsletter_subscriber';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return 'app.form.earning_rule.condition.newsletter_subscriber';
    }

    public function getConfigurationFormType(): ?string
    {
        return null;
    }

    public function check(array $configuration, EarningContext $context): bool
    {
        return $context->customer?->isSubscribedToNewsletter() ?? false;
    }
}
```

That is the whole integration: the plugin registers `ConditionCheckerInterface` for
autoconfiguration, so any service implementing it is tagged
`setono_sylius_loyalty.earning_condition` automatically. Add the
`app.form.earning_rule.condition.newsletter_subscriber` translation and the type appears in the
condition type select of the admin earning rule form.

If your condition needs configuration (like the built-in `order_total_at_least` checker, which
reads `$configuration['amount']`), the admin enters it in the rule form's JSON configuration
field and it arrives in `check()` as an array — validate the shape defensively and return
`false` on garbage, never throw. `getConfigurationFormType()` is the hook for rendering a
dedicated configuration form type instead of the JSON field.

Only if your project has `autoconfigure: false` do you need to tag manually:

```yaml
# config/services.yaml
App\Loyalty\Checker\NewsletterSubscriberConditionChecker:
    tags: ['setono_sylius_loyalty.earning_condition']
```
