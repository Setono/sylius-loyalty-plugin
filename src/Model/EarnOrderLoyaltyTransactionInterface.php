<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\OrderInterface;

/**
 * Points earned for an order. Written at most once per (account, order) — enforced by a
 * database unique constraint.
 */
interface EarnOrderLoyaltyTransactionInterface extends CreditLoyaltyTransactionInterface, RulesBreakdownAwareInterface
{
    public function getOrder(): ?OrderInterface;

    public function setOrder(?OrderInterface $order): void;

    /**
     * The eligible basis (minor units, channel base currency) the points were computed on.
     */
    public function getBasisAmount(): ?int;

    public function setBasisAmount(?int $basisAmount): void;
}
