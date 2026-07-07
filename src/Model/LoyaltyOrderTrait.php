<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Add this trait (and LoyaltyOrderInterface) to the project's Order entity, then generate the
 * column with doctrine:migrations:diff — the plugin ships no migrations. The property carries
 * an ORM attribute; projects mapping their order with XML or YAML map the column themselves.
 */
trait LoyaltyOrderTrait
{
    #[ORM\Column(name: 'loyalty_points_requested', type: 'integer', nullable: true)]
    protected ?int $loyaltyPointsRequested = null;

    public function getLoyaltyPointsRequested(): ?int
    {
        return $this->loyaltyPointsRequested;
    }

    public function setLoyaltyPointsRequested(?int $loyaltyPointsRequested): void
    {
        $this->loyaltyPointsRequested = $loyaltyPointsRequested;
    }
}
