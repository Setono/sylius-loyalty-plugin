<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * The abstract root of the append-only points ledger (Doctrine single-table inheritance).
 *
 * Rows are never updated or deleted; corrections are new compensating entries. `points` is signed:
 * positive for credits, negative for debits. Concrete types are suffixed `LoyaltyTransaction` and
 * their discriminator values are the snake_case type without that suffix (e.g. `earn_order`).
 */
interface LoyaltyTransactionInterface extends ResourceInterface
{
    /**
     * The discriminator value of this transaction type (e.g. `earn_order`, `redeem`).
     *
     * The discriminator-map listener reads this from every registered transaction resource to build
     * the STI discriminator map, so a project can add a transaction type just by registering it.
     */
    public static function getType(): string;

    public function getId(): ?int;

    public function getAccount(): ?LoyaltyAccountInterface;

    public function setAccount(?LoyaltyAccountInterface $account): void;

    public function getPoints(): int;

    public function setPoints(int $points): void;

    public function getOccurredAt(): ?\DateTimeInterface;

    public function setOccurredAt(?\DateTimeInterface $occurredAt): void;
}
