<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Expression;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;
use Setono\SyliusLoyaltyPlugin\Exception\InvalidExpressionException;
use Setono\SyliusLoyaltyPlugin\Expression\ExpressionCatalog;
use Setono\SyliusLoyaltyPlugin\Expression\ExpressionEvaluator;
use Setono\SyliusLoyaltyPlugin\Expression\ExpressionValidator;
use Setono\SyliusLoyaltyPlugin\Expression\Function\DayOfWeekFunction;
use Setono\SyliusLoyaltyPlugin\Expression\Function\ExpressionFunctionRegistry;
use Setono\SyliusLoyaltyPlugin\Expression\Function\MathFunction;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

final class ExpressionSandboxTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_accepts_whitelisted_property_chains(): void
    {
        $this->validator()->validate('customer.group.code == "vip" and account.balance > 100');

        $this->expectNotToPerformAssertions();
    }

    /**
     * @test
     */
    public function it_rejects_unknown_variables(): void
    {
        $this->expectException(InvalidExpressionException::class);

        $this->validator()->validate('container.get("doctrine")');
    }

    /**
     * @test
     */
    public function it_rejects_non_whitelisted_members(): void
    {
        $this->expectException(InvalidExpressionException::class);
        $this->expectExceptionMessage('"password" is not available on "customer"');

        $this->validator()->validate('customer.password');
    }

    /**
     * @test
     */
    public function it_rejects_method_calls(): void
    {
        $this->expectException(InvalidExpressionException::class);
        $this->expectExceptionMessage('Method calls are not available');

        $this->validator()->validate('customer.getEmail()');
    }

    /**
     * @test
     */
    public function it_rejects_unknown_functions(): void
    {
        $this->expectException(InvalidExpressionException::class);

        $this->validator()->validate('phpinfo()');
    }

    /**
     * @test
     */
    public function it_rejects_order_variables_for_action_triggers(): void
    {
        $this->expectException(InvalidExpressionException::class);

        $this->validator()->validate('basis > 100', 'customer_registered');
    }

    /**
     * @test
     */
    public function it_accepts_order_variables_for_the_order_trigger(): void
    {
        $this->validator()->validate('basis > 100', EarningRuleInterface::TRIGGER_ORDER_ELIGIBLE);

        $this->expectNotToPerformAssertions();
    }

    /**
     * @test
     */
    public function it_rejects_syntax_errors(): void
    {
        $this->expectException(InvalidExpressionException::class);

        $this->validator()->validate('basis >');
    }

    /**
     * @test
     */
    public function it_evaluates_against_curated_views(): void
    {
        $customer = $this->prophesize(CustomerInterface::class);
        $customer->getEmail()->willReturn('jane@example.com');
        $customer->getFirstName()->willReturn('Jane');
        $customer->getLastName()->willReturn('Doe');
        $customer->getGroup()->willReturn(null);

        $channel = $this->prophesize(ChannelInterface::class);
        $channel->getCode()->willReturn('WEB');
        $channel->getName()->willReturn('Web store');

        $context = new EarningContext(
            channel: $channel->reveal(),
            customer: $customer->reveal(),
            itemAmounts: [1 => 60000],
        );

        $result = $this->evaluator()->evaluate('basis > 50000 ? floor(basis / 50) : floor(basis / 100)', $context);
        self::assertSame(1200.0, $result);

        self::assertSame('jane@example.com', $this->evaluator()->evaluate('customer.email', $context));
        self::assertSame('WEB', $this->evaluator()->evaluate('channel.code', $context));
    }

    /**
     * @test
     */
    public function it_overrides_the_basis_for_scoped_rules(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);
        $channel->getCode()->willReturn('WEB');
        $channel->getName()->willReturn('Web store');

        $context = new EarningContext(channel: $channel->reveal(), itemAmounts: [1 => 60000]);

        self::assertSame(4000, $this->evaluator()->evaluate('basis', $context, 4000));
    }

    /**
     * @test
     */
    public function it_exposes_registered_functions(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);
        $channel->getCode()->willReturn('WEB');
        $channel->getName()->willReturn('Web store');

        $context = new EarningContext(
            channel: $channel->reveal(),
            now: new \DateTimeImmutable('2026-07-06'), // a Monday
        );

        self::assertSame(1, $this->evaluator()->evaluate('day_of_week()', $context));
    }

    private function validator(): ExpressionValidator
    {
        return new ExpressionValidator(new ExpressionCatalog($this->functions()), $this->functions());
    }

    private function evaluator(): ExpressionEvaluator
    {
        return new ExpressionEvaluator($this->functions(), $this->validator());
    }

    private function functions(): ExpressionFunctionRegistry
    {
        $registry = new ExpressionFunctionRegistry();
        $registry->add(new DayOfWeekFunction());
        foreach (MathFunction::NAMES as $name) {
            $registry->add(new MathFunction($name));
        }

        return $registry;
    }
}
