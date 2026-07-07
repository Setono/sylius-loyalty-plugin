<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Model;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccount;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

final class LoyaltyAccountTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_has_the_documented_defaults(): void
    {
        $account = new LoyaltyAccount();

        self::assertNull($account->getId());
        self::assertNull($account->getCustomer());
        self::assertNull($account->getChannel());
        self::assertSame(0, $account->getBalance());
        self::assertSame(0, $account->getLifetimeEarned());
        self::assertNull($account->getReferralCode());
        self::assertTrue($account->isEnabled());
    }

    /**
     * @test
     */
    public function it_associates_with_a_customer_and_a_channel(): void
    {
        $account = new LoyaltyAccount();
        $customer = $this->prophesize(CustomerInterface::class)->reveal();
        $channel = $this->prophesize(ChannelInterface::class)->reveal();

        $account->setCustomer($customer);
        $account->setChannel($channel);

        self::assertSame($customer, $account->getCustomer());
        self::assertSame($channel, $account->getChannel());
    }

    /**
     * @test
     */
    public function it_holds_cached_balance_and_lifetime_earned(): void
    {
        $account = new LoyaltyAccount();

        $account->setBalance(1250);
        $account->setLifetimeEarned(5000);
        $account->setReferralCode('ABCD1234');
        $account->disable();

        self::assertSame(1250, $account->getBalance());
        self::assertSame(5000, $account->getLifetimeEarned());
        self::assertSame('ABCD1234', $account->getReferralCode());
        self::assertFalse($account->isEnabled());
    }
}
