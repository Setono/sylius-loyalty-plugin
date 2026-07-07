<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusLoyaltyPlugin\Model\EarningRule;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleCondition;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;

final class EarningRuleTest extends TestCase
{
    /**
     * @test
     */
    public function it_defaults_to_an_enabled_agnostic_order_scoped_all_matching_stackable_rule(): void
    {
        $rule = new EarningRule();

        self::assertNull($rule->getId());
        self::assertSame(EarningRuleInterface::SCOPE_ORDER, $rule->getScope());
        self::assertSame(EarningRuleInterface::CONDITIONS_MATCH_ALL, $rule->getConditionsMatch());
        self::assertTrue($rule->isStackable());
        self::assertFalse($rule->isDryRun());
        self::assertSame(0, $rule->getPriority());
        self::assertSame([], $rule->getScopeConfiguration());
        self::assertSame([], $rule->getAmountConfiguration());
        self::assertCount(0, $rule->getConditions());
    }

    /**
     * @test
     */
    public function it_holds_its_trigger_scope_amount_and_window(): void
    {
        $startsAt = new \DateTimeImmutable('2026-11-27 00:00:00');
        $endsAt = new \DateTimeImmutable('2026-11-28 00:00:00');

        $rule = new EarningRule();
        $rule->setName('Black Friday double points');
        $rule->setTrigger('order_eligible');
        $rule->setScope(EarningRuleInterface::SCOPE_PRODUCT);
        $rule->setScopeConfiguration(['PRODUCT_X', 'PRODUCT_Y']);
        $rule->setConditionsMatch(EarningRuleInterface::CONDITIONS_MATCH_ANY);
        $rule->setAmountType('multiplier');
        $rule->setAmountConfiguration(['multiplier' => 2.0]);
        $rule->setPriority(10);
        $rule->setStackable(false);
        $rule->setDryRun(true);
        $rule->setStartsAt($startsAt);
        $rule->setEndsAt($endsAt);

        self::assertSame('Black Friday double points', $rule->getName());
        self::assertSame('order_eligible', $rule->getTrigger());
        self::assertSame(EarningRuleInterface::SCOPE_PRODUCT, $rule->getScope());
        self::assertSame(['PRODUCT_X', 'PRODUCT_Y'], $rule->getScopeConfiguration());
        self::assertSame(EarningRuleInterface::CONDITIONS_MATCH_ANY, $rule->getConditionsMatch());
        self::assertSame('multiplier', $rule->getAmountType());
        self::assertSame(['multiplier' => 2.0], $rule->getAmountConfiguration());
        self::assertSame(10, $rule->getPriority());
        self::assertFalse($rule->isStackable());
        self::assertTrue($rule->isDryRun());
        self::assertSame($startsAt, $rule->getStartsAt());
        self::assertSame($endsAt, $rule->getEndsAt());
    }

    /**
     * @test
     */
    public function scope_configuration_is_stored_as_a_reindexed_list(): void
    {
        $rule = new EarningRule();
        $rule->setScopeConfiguration([2 => 'TAXON_A', 5 => 'TAXON_B']);

        self::assertSame(['TAXON_A', 'TAXON_B'], $rule->getScopeConfiguration());
    }

    /**
     * @test
     */
    public function adding_a_condition_links_it_back_to_the_rule(): void
    {
        $rule = new EarningRule();
        $condition = new EarningRuleCondition();

        $rule->addCondition($condition);

        self::assertTrue($rule->hasCondition($condition));
        self::assertCount(1, $rule->getConditions());
        self::assertSame($rule, $condition->getRule());
    }

    /**
     * @test
     */
    public function a_condition_is_not_added_twice(): void
    {
        $rule = new EarningRule();
        $condition = new EarningRuleCondition();

        $rule->addCondition($condition);
        $rule->addCondition($condition);

        self::assertCount(1, $rule->getConditions());
    }

    /**
     * @test
     */
    public function removing_a_condition_unlinks_it(): void
    {
        $rule = new EarningRule();
        $condition = new EarningRuleCondition();
        $rule->addCondition($condition);

        $rule->removeCondition($condition);

        self::assertFalse($rule->hasCondition($condition));
        self::assertCount(0, $rule->getConditions());
        self::assertNull($condition->getRule());
    }

    /**
     * @test
     */
    public function a_condition_carries_its_type_and_configuration(): void
    {
        $condition = new EarningRuleCondition();
        $condition->setType('order_total_at_least');
        $condition->setConfiguration(['amount' => 5000]);

        self::assertSame('order_total_at_least', $condition->getType());
        self::assertSame(['amount' => 5000], $condition->getConfiguration());
    }
}
