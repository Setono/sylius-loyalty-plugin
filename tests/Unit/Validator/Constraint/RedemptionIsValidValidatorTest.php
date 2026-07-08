<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Validator\Constraint;

use Doctrine\Common\Collections\ArrayCollection;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Redemption\RedemptionOrderProcessor;
use Setono\SyliusLoyaltyPlugin\Validator\Constraint\RedemptionIsValid;
use Setono\SyliusLoyaltyPlugin\Validator\Constraint\RedemptionIsValidValidator;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<RedemptionIsValidValidator>
 */
final class RedemptionIsValidValidatorTest extends ConstraintValidatorTestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<LoyaltyAccountProviderInterface> */
    private ObjectProphecy $accountProvider;

    /**
     * @test
     */
    public function it_raises_no_violation_when_the_balance_covers_the_redemption(): void
    {
        $this->accountProvider->getAccount(Argument::cetera())->willReturn($this->account(500, true));

        $this->validator->validate($this->orderWithRedemption(300), new RedemptionIsValid());

        $this->assertNoViolation();
    }

    /**
     * @test
     */
    public function it_raises_a_violation_when_the_balance_is_insufficient(): void
    {
        $this->accountProvider->getAccount(Argument::cetera())->willReturn($this->account(100, true));

        $constraint = new RedemptionIsValid();
        $this->validator->validate($this->orderWithRedemption(300), $constraint);

        $this->buildViolation($constraint->insufficientBalanceMessage)->assertRaised();
    }

    /**
     * @test
     */
    public function it_raises_a_violation_when_the_account_is_disabled(): void
    {
        $this->accountProvider->getAccount(Argument::cetera())->willReturn($this->account(500, false));

        $constraint = new RedemptionIsValid();
        $this->validator->validate($this->orderWithRedemption(300), $constraint);

        $this->buildViolation($constraint->accountDisabledMessage)->assertRaised();
    }

    /**
     * @test
     */
    public function it_raises_no_violation_for_an_order_without_a_redemption(): void
    {
        $this->validator->validate($this->orderWithRedemption(0), new RedemptionIsValid());

        $this->assertNoViolation();
    }

    /**
     * @test
     */
    public function it_ignores_a_value_that_is_not_an_order(): void
    {
        $this->validator->validate(new \stdClass(), new RedemptionIsValid());

        $this->assertNoViolation();
    }

    /**
     * @test
     */
    public function it_ignores_a_guest_order(): void
    {
        $adjustment = $this->prophesize(AdjustmentInterface::class);
        $adjustment->getDetails()->willReturn(['points' => 300]);

        $order = $this->prophesize(OrderInterface::class);
        $order->getAdjustments(RedemptionOrderProcessor::ADJUSTMENT_TYPE)->willReturn(new ArrayCollection([$adjustment->reveal()]));
        $order->getCustomer()->willReturn(null);
        $order->getChannel()->willReturn($this->prophesize(ChannelInterface::class)->reveal());

        $this->validator->validate($order->reveal(), new RedemptionIsValid());

        $this->assertNoViolation();
    }

    /**
     * @test
     */
    public function it_throws_for_an_unexpected_constraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->orderWithRedemption(300), new NotNull());
    }

    /**
     * @test
     */
    public function it_is_a_class_level_constraint(): void
    {
        self::assertSame(RedemptionIsValid::CLASS_CONSTRAINT, (new RedemptionIsValid())->getTargets());
    }

    protected function createValidator(): RedemptionIsValidValidator
    {
        $this->accountProvider = $this->prophesize(LoyaltyAccountProviderInterface::class);

        return new RedemptionIsValidValidator($this->accountProvider->reveal());
    }

    private function account(int $balance, bool $enabled): LoyaltyAccountInterface
    {
        $account = $this->prophesize(LoyaltyAccountInterface::class);
        $account->isEnabled()->willReturn($enabled);
        $account->getBalance()->willReturn($balance);

        return $account->reveal();
    }

    private function orderWithRedemption(int $points): OrderInterface
    {
        $adjustment = $this->prophesize(AdjustmentInterface::class);
        $adjustment->getDetails()->willReturn(['points' => $points]);

        $order = $this->prophesize(OrderInterface::class);
        $order->getAdjustments(RedemptionOrderProcessor::ADJUSTMENT_TYPE)->willReturn(new ArrayCollection([$adjustment->reveal()]));
        $order->getCustomer()->willReturn($this->prophesize(CustomerInterface::class)->reveal());
        $order->getChannel()->willReturn($this->prophesize(ChannelInterface::class)->reveal());

        return $order->reveal();
    }
}
