<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Rule\Basis;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Sylius\Component\Core\Model\OrderInterface;

interface EarningBasisProviderInterface
{
    /**
     * The eligible amount (in minor units) an order earns over, per the program's earningBasis and
     * includeTaxes settings.
     */
    public function getBasis(OrderInterface $order, LoyaltyProgramInterface $program): int;
}
