<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Channel\Model\ChannelAwareInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\ToggleableInterface;

/**
 * A configurable earning rule: a trigger, a set of conditions, and an amount.
 */
interface EarningRuleInterface extends ResourceInterface, ToggleableInterface, ChannelAwareInterface
{
    /**
     * The built-in order trigger, evaluated inside the order earning pipeline. All other
     * triggers are action triggers backed by event classes extending EarningTriggerEvent.
     */
    public const TRIGGER_ORDER_ELIGIBLE = 'order_eligible';

    public const SCOPE_ORDER = 'order';

    public const SCOPE_TAXON = 'taxon';

    public const SCOPE_PRODUCT = 'product';

    public const CONDITIONS_MATCH_ALL = 'all';

    public const CONDITIONS_MATCH_ANY = 'any';

    public function getName(): ?string;

    public function setName(?string $name): void;

    /**
     * An enabled rule with dry run on evaluates against live traffic and logs what it would
     * award (as DryRunResult rows) without writing ledger entries.
     */
    public function isDryRun(): bool;

    public function setDryRun(bool $dryRun): void;

    /**
     * Among rules competing for the same basis, higher priority wins where stacking rules out.
     */
    public function getPriority(): int;

    public function setPriority(int $priority): void;

    public function getTrigger(): string;

    public function setTrigger(string $trigger): void;

    public function getScope(): string;

    public function setScope(string $scope): void;

    /**
     * Taxon codes or product codes, depending on the scope.
     *
     * @return array<string, mixed>
     */
    public function getScopeConfiguration(): array;

    /**
     * @param array<string, mixed> $scopeConfiguration
     */
    public function setScopeConfiguration(array $scopeConfiguration): void;

    public function getConditionsMatch(): string;

    public function setConditionsMatch(string $conditionsMatch): void;

    /**
     * @return Collection<array-key, EarningRuleConditionInterface>
     */
    public function getConditions(): Collection;

    public function addCondition(EarningRuleConditionInterface $condition): void;

    public function removeCondition(EarningRuleConditionInterface $condition): void;

    public function hasCondition(EarningRuleConditionInterface $condition): bool;

    public function getAmountType(): ?string;

    public function setAmountType(?string $amountType): void;

    /**
     * @return array<string, mixed>
     */
    public function getAmountConfiguration(): array;

    /**
     * @param array<string, mixed> $amountConfiguration
     */
    public function setAmountConfiguration(array $amountConfiguration): void;

    /**
     * Start of the rule's active window, evaluated in the application's configured timezone.
     */
    public function getStartsAt(): ?\DateTimeImmutable;

    public function setStartsAt(?\DateTimeImmutable $startsAt): void;

    public function getEndsAt(): ?\DateTimeImmutable;

    public function setEndsAt(?\DateTimeImmutable $endsAt): void;

    public function isStackable(): bool;

    public function setStackable(bool $stackable): void;
}
