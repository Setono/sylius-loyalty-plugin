<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyOrderInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyOrderTrait;
use Sylius\Component\Core\Model\Order as BaseOrder;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_order')]
class Order extends BaseOrder implements LoyaltyOrderInterface
{
    use LoyaltyOrderTrait;
}
