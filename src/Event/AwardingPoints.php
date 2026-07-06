<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Event;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * Dispatched before an earn credit is written. Listeners may adjust the points, adjust the
 * expiry, or cancel the write entirely.
 */
final class AwardingPoints
{
    use CancellableTrait;

    /**
     * @param array<string, mixed> $rulesBreakdown
     */
    public function __construct(
        private readonly LoyaltyAccountInterface $account,
        private int $points,
        private ?\DateTimeImmutable $expiresAt,
        private readonly ?OrderInterface $order = null,
        private readonly ?string $sourceIdentifier = null,
        private readonly array $rulesBreakdown = [],
    ) {
    }

    public function getAccount(): LoyaltyAccountInterface
    {
        return $this->account;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): void
    {
        $this->points = $points;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    /**
     * The order being awarded for. Null for action-trigger earning.
     */
    public function getOrder(): ?OrderInterface
    {
        return $this->order;
    }

    /**
     * The deduplication identifier. Null for order earning.
     */
    public function getSourceIdentifier(): ?string
    {
        return $this->sourceIdentifier;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRulesBreakdown(): array
    {
        return $this->rulesBreakdown;
    }
}
