<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Redemption;

use Sylius\Component\Core\Model\OrderInterface;

/**
 * The points currently applied to an order — derived from its redemption adjustments, which
 * always correspond exactly to a clean multiple of the conversion. The checkout debit uses
 * this value, never the raw request.
 */
interface AppliedPointsProviderInterface
{
    public function getAppliedPoints(OrderInterface $order): int;
}
