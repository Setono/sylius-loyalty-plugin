<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Doctrine\ORM\Mapping as ORM;

trait LoyaltyOrderTrait
{
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    protected int $loyaltyPointsRequested = 0;

    public function getLoyaltyPointsRequested(): int
    {
        return $this->loyaltyPointsRequested;
    }

    public function setLoyaltyPointsRequested(int $loyaltyPointsRequested): void
    {
        $this->loyaltyPointsRequested = max(0, $loyaltyPointsRequested);
    }
}
