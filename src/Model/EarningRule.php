<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Model\ToggleableTrait;

class EarningRule implements EarningRuleInterface
{
    use ToggleableTrait;

    protected ?int $id = null;

    protected ?ChannelInterface $channel = null;

    protected ?string $name = null;

    protected bool $dryRun = false;

    protected int $priority = 0;

    protected ?string $trigger = null;

    protected string $scope = self::SCOPE_ORDER;

    /** @var list<string> */
    protected array $scopeConfiguration = [];

    protected string $conditionsMatch = self::CONDITIONS_MATCH_ALL;

    /** @var Collection<int, EarningRuleConditionInterface> */
    protected Collection $conditions;

    protected ?string $amountType = null;

    /** @var array<string, mixed> */
    protected array $amountConfiguration = [];

    protected ?\DateTimeInterface $startsAt = null;

    protected ?\DateTimeInterface $endsAt = null;

    protected bool $stackable = true;

    public function __construct()
    {
        $this->conditions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(?ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    public function setDryRun(bool $dryRun): void
    {
        $this->dryRun = $dryRun;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function getTrigger(): ?string
    {
        return $this->trigger;
    }

    public function setTrigger(?string $trigger): void
    {
        $this->trigger = $trigger;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    public function getScopeConfiguration(): array
    {
        return $this->scopeConfiguration;
    }

    public function setScopeConfiguration(array $scopeConfiguration): void
    {
        $this->scopeConfiguration = array_values($scopeConfiguration);
    }

    public function getConditionsMatch(): string
    {
        return $this->conditionsMatch;
    }

    public function setConditionsMatch(string $conditionsMatch): void
    {
        $this->conditionsMatch = $conditionsMatch;
    }

    public function getConditions(): Collection
    {
        return $this->conditions;
    }

    public function addCondition(EarningRuleConditionInterface $condition): void
    {
        if (!$this->hasCondition($condition)) {
            $condition->setRule($this);
            $this->conditions->add($condition);
        }
    }

    public function removeCondition(EarningRuleConditionInterface $condition): void
    {
        if ($this->hasCondition($condition)) {
            $condition->setRule(null);
            $this->conditions->removeElement($condition);
        }
    }

    public function hasCondition(EarningRuleConditionInterface $condition): bool
    {
        return $this->conditions->contains($condition);
    }

    public function getAmountType(): ?string
    {
        return $this->amountType;
    }

    public function setAmountType(?string $amountType): void
    {
        $this->amountType = $amountType;
    }

    public function getAmountConfiguration(): array
    {
        return $this->amountConfiguration;
    }

    public function setAmountConfiguration(array $amountConfiguration): void
    {
        $this->amountConfiguration = $amountConfiguration;
    }

    public function getStartsAt(): ?\DateTimeInterface
    {
        return $this->startsAt;
    }

    public function setStartsAt(?\DateTimeInterface $startsAt): void
    {
        $this->startsAt = $startsAt;
    }

    public function getEndsAt(): ?\DateTimeInterface
    {
        return $this->endsAt;
    }

    public function setEndsAt(?\DateTimeInterface $endsAt): void
    {
        $this->endsAt = $endsAt;
    }

    public function isStackable(): bool
    {
        return $this->stackable;
    }

    public function setStackable(bool $stackable): void
    {
        $this->stackable = $stackable;
    }
}
