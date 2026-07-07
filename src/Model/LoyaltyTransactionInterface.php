<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * A single entry in the strictly append-only loyalty ledger. Rows are never updated or
 * deleted; corrections are new compensating entries. All writes go through the ledger service.
 */
interface LoyaltyTransactionInterface extends ResourceInterface
{
    public function getAccount(): ?LoyaltyAccountInterface;

    public function setAccount(?LoyaltyAccountInterface $account): void;

    /**
     * Signed: positive for credits, negative for debits.
     */
    public function getPoints(): int;

    public function setPoints(int $points): void;

    public function getOccurredAt(): \DateTimeImmutable;

    public function setOccurredAt(\DateTimeImmutable $occurredAt): void;
}
