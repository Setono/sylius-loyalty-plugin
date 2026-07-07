# Adding a custom expression function

Expression conditions run in a sandboxed expression language with domain functions like
`orders_count()` and `taxon_total('...')`. A custom function is a service implementing
`Setono\SyliusLoyaltyPlugin\Expression\Function\ExpressionFunctionInterface`:

```php
interface ExpressionFunctionInterface
{
    public function getName(): string;

    /** E.g. "taxon_total(taxonCode: string): int". */
    public function getSignature(): string;

    /** A translation key describing the function. */
    public function getDescription(): string;

    public function evaluate(EarningContext $context, mixed ...$arguments): mixed;
}
```

## Example: days since registration

```php
<?php

declare(strict_types=1);

namespace App\Loyalty\Expression;

use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;
use Setono\SyliusLoyaltyPlugin\Expression\Function\ExpressionFunctionInterface;

final class DaysSinceRegistrationFunction implements ExpressionFunctionInterface
{
    public function getName(): string
    {
        return 'days_since_registration';
    }

    public function getSignature(): string
    {
        return 'days_since_registration(): int';
    }

    public function getDescription(): string
    {
        return 'app.expression.function.days_since_registration';
    }

    public function evaluate(EarningContext $context, mixed ...$arguments): mixed
    {
        $createdAt = $context->customer?->getCreatedAt();
        if (null === $createdAt) {
            return 0;
        }

        // Use the context clock so the admin rule tester can preview scheduled rules
        return $createdAt->diff($context->getNow())->days;
    }
}
```

Implementing the interface is the whole integration: the plugin registers
`ExpressionFunctionInterface` for autoconfiguration, so the service is tagged
`setono_sylius_loyalty.expression_function` automatically. The metadata (`getName()`,
`getSignature()`, `getDescription()`) feeds the admin expression editor's **autocompletion**
and the generated **reference panel**, so your function shows up in both without further work —
add the translation for the description key.

An expression like this then validates and evaluates:

```
days_since_registration() >= 365 and orders_count() >= 3
```

Guidelines:

- Read state from the `EarningContext` (`channel`, `customer`, `order`, trigger `context`, ...)
  and return early with a neutral value when a part is missing — action-trigger earning has no
  order, for example.
- Use `$context->getNow()` for time, never `new \DateTimeImmutable()`.
- Validate `$arguments` defensively; expressions are admin-authored.

Only if your project has `autoconfigure: false` do you need to tag manually:

```yaml
# config/services.yaml
App\Loyalty\Expression\DaysSinceRegistrationFunction:
    tags: ['setono_sylius_loyalty.expression_function']
```
