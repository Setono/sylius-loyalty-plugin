<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * What a dry-run rule would have awarded, logged for the admin audit list and pruned after
 * 30 days.
 */
interface DryRunResultInterface extends ResourceInterface
{
    public function getRule(): ?EarningRuleInterface;

    public function setRule(?EarningRuleInterface $rule): void;

    public function getAccount(): ?LoyaltyAccountInterface;

    public function setAccount(?LoyaltyAccountInterface $account): void;

    public function getOrder(): ?OrderInterface;

    public function setOrder(?OrderInterface $order): void;

    public function getPoints(): int;

    public function setPoints(int $points): void;

    /**
     * @return array<string, mixed>
     */
    public function getDetails(): array;

    /**
     * @param array<string, mixed> $details
     */
    public function setDetails(array $details): void;

    public function getCreatedAt(): \DateTimeImmutable;
}
