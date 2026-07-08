<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Application\Model;

use Doctrine\ORM\Mapping as ORM;
use Setono\SyliusLoyaltyPlugin\Model\OrderInterface;
use Setono\SyliusLoyaltyPlugin\Model\OrderTrait;
use Sylius\Component\Core\Model\Order as BaseOrder;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_order')]
class Order extends BaseOrder implements OrderInterface
{
    use OrderTrait;
}
