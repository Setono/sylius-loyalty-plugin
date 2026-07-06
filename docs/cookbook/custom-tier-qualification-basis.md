# Custom tier qualification basis

Tiers qualify on a metric computed by a *qualification basis*. The plugin ships `points_earned`,
`amount_spent`, and `orders_count`; adding your own makes it appear in the tier form's basis
select automatically.

Implement `TierQualificationBasisInterface`:

```php
<?php

declare(strict_types=1);

namespace App\Loyalty;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Tier\QualificationBasis\DateRange;
use Setono\SyliusLoyaltyPlugin\Tier\QualificationBasis\TierQualificationBasisInterface;

final class ReviewsWrittenBasis implements TierQualificationBasisInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function getCode(): string
    {
        return 'reviews_written';
    }

    public function getLabel(): string
    {
        return 'app.tier_basis.reviews_written';
    }

    public function getUnitLabel(): string
    {
        return 'app.tier_basis.unit.reviews';
    }

    public function calculate(LoyaltyAccountInterface $account, ?DateRange $window): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(\Sylius\Component\Core\Model\ProductReview::class, 'r')
            ->andWhere('r.author = :customer')
            ->andWhere('r.status = :accepted')
            ->setParameter('customer', $account->getCustomer())
            ->setParameter('accepted', 'accepted')
        ;

        // A null window means lifetime. A custom basis may interpret or ignore the window —
        // that is the escape hatch for exotic windows.
        if (null !== $window) {
            $qb->andWhere('r.createdAt >= :start')->setParameter('start', $window->start)
               ->andWhere('r.createdAt <= :end')->setParameter('end', $window->end);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
```

With autoconfiguration enabled (the default), implementing the interface is the whole
integration — the class is tagged `setono_sylius_loyalty.tier_qualification_basis`
automatically. Without autoconfigure, tag it yourself:

```yaml
services:
    App\Loyalty\ReviewsWrittenBasis:
        tags: ['setono_sylius_loyalty.tier_qualification_basis']
```

Notes:

- `calculate()` runs inside the ledger transaction on inline upgrades, so keep it a cheap
  aggregate query — never a replay.
- The window resolved from the program (`calendar_year` | `rolling_12_months` | `lifetime`)
  is handed to you; use inclusive bounds (`>= start`, `<= end`) so activity from the very
  second of the evaluation counts.
- The unit label shows up as the threshold field's hint in the tier form.
