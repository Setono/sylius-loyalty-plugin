<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\ToggleableInterface;

interface EarningRuleInterface extends ResourceInterface, ToggleableInterface
{
    public const SCOPE_ORDER = 'order';

    public const SCOPE_TAXON = 'taxon';

    public const SCOPE_PRODUCT = 'product';

    public const CONDITIONS_MATCH_ALL = 'all';

    public const CONDITIONS_MATCH_ANY = 'any';

    public function getId(): ?int;

    public function getChannel(): ?ChannelInterface;

    public function setChannel(?ChannelInterface $channel): void;

    public function getName(): ?string;

    public function setName(?string $name): void;

    /**
     * A dry-run rule is evaluated and logged but never awards points — used to preview a rule's
     * effect against live traffic before enabling it for real.
     */
    public function isDryRun(): bool;

    public function setDryRun(bool $dryRun): void;

    public function getPriority(): int;

    public function setPriority(int $priority): void;

    /**
     * The earning trigger this rule reacts to, e.g. `order_eligible` or an action trigger code.
     */
    public function getTrigger(): ?string;

    public function setTrigger(?string $trigger): void;

    /**
     * The basis this rule earns over — one of the SCOPE_* constants: the whole order, or the items
     * in a taxon/product set.
     */
    public function getScope(): string;

    public function setScope(string $scope): void;

    /**
     * @return list<string> taxon or product codes, depending on the scope
     */
    public function getScopeConfiguration(): array;

    /**
     * @param array<array-key, string> $scopeConfiguration stored reindexed as a list
     */
    public function setScopeConfiguration(array $scopeConfiguration): void;

    /**
     * Whether every condition must pass (`all`) or at least one (`any`) — one of the CONDITIONS_MATCH_* constants.
     */
    public function getConditionsMatch(): string;

    public function setConditionsMatch(string $conditionsMatch): void;

    /**
     * @return Collection<int, EarningRuleConditionInterface>
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

    public function getStartsAt(): ?\DateTimeInterface;

    public function setStartsAt(?\DateTimeInterface $startsAt): void;

    public function getEndsAt(): ?\DateTimeInterface;

    public function setEndsAt(?\DateTimeInterface $endsAt): void;

    public function isStackable(): bool;

    public function setStackable(bool $stackable): void;
}
