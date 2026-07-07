# Adding a custom amount type

An amount type decides how many points a matched earning rule awards. It is a service
implementing `Setono\SyliusLoyaltyPlugin\EarningRule\Amount\AmountCalculatorInterface`:

```php
interface AmountCalculatorInterface
{
    public function getType(): string;

    /** A translation key for the rule form's amount type select. */
    public function getLabel(): string;

    /** @return class-string|null The form type rendering this amount's configuration, or null if it has none. */
    public function getConfigurationFormType(): ?string;

    /**
     * Returns the (unrounded) points for the claimed basis. The program's rounding is applied
     * once on the final total, not per rule.
     *
     * @param array<string, mixed> $configuration
     */
    public function calculate(array $configuration, AmountCalculationInput $input): float;
}
```

`calculate()` receives an `Setono\SyliusLoyaltyPlugin\EarningRule\Amount\AmountCalculationInput`
describing the basis the rule claimed during evaluation:

- `$input->basisAmount` — the eligible amount in minor units of the channel base currency
- `$input->units` — the number of matching units (order-scoped rules always claim with
  `units = 1`; item-scoped rules claim the matching items' quantities)
- `$input->context` — the full `EarningContext` (channel, customer, order, trigger context, ...)

Return a `float` and do not round — the program's rounding mode (floor/round/ceil) is applied
once on the rule total.

## Example: points per amount, capped

Like the built-in `per_amount` calculator ("X points per Y minor units of claimed basis") but
with a per-award ceiling:

```php
<?php

declare(strict_types=1);

namespace App\Loyalty\Amount;

use Setono\SyliusLoyaltyPlugin\EarningRule\Amount\AmountCalculationInput;
use Setono\SyliusLoyaltyPlugin\EarningRule\Amount\AmountCalculatorInterface;

final class CappedPerAmountCalculator implements AmountCalculatorInterface
{
    public const TYPE = 'app_capped_per_amount';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return 'app.form.earning_rule.amount.capped_per_amount';
    }

    public function getConfigurationFormType(): ?string
    {
        return null;
    }

    public function calculate(array $configuration, AmountCalculationInput $input): float
    {
        $points = $configuration['points'] ?? null;
        $perAmount = $configuration['per_amount'] ?? null;
        $max = $configuration['max'] ?? null;

        if ((!is_int($points) && !is_float($points)) || !is_int($perAmount) || $perAmount < 1 || !is_int($max)) {
            return 0.0;
        }

        return min($input->basisAmount / $perAmount * (float) $points, (float) $max);
    }
}
```

Implementing the interface is the whole integration: the plugin registers
`AmountCalculatorInterface` for autoconfiguration, so the service is tagged
`setono_sylius_loyalty.earning_amount` automatically and the type appears in the amount type
select of the admin earning rule form (add the translation for your label key). The
configuration is entered in the rule form and passed to `calculate()` as an array — validate it
defensively and return `0.0` on invalid shapes.

Only if your project has `autoconfigure: false` do you need to tag manually:

```yaml
# config/services.yaml
App\Loyalty\Amount\CappedPerAmountCalculator:
    tags: ['setono_sylius_loyalty.earning_amount']
```
