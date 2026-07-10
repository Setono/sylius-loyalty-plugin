<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Unit\Controller\Shop;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusLoyaltyPlugin\Controller\Shop\ShowLoyaltyAccountAction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Sylius\Component\Core\Context\ShopperContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Twig\Environment;

final class ShowLoyaltyAccountActionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_renders_the_page_with_the_account_and_its_recent_history(): void
    {
        $customer = $this->prophesize(CustomerInterface::class)->reveal();
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $account = $this->prophesize(LoyaltyAccountInterface::class)->reveal();
        $transaction = $this->prophesize(LoyaltyTransactionInterface::class)->reveal();

        $shopperContext = $this->prophesize(ShopperContextInterface::class);
        $shopperContext->getCustomer()->willReturn($customer);
        $shopperContext->getChannel()->willReturn($channel);

        $accountRepository = $this->prophesize(LoyaltyAccountRepositoryInterface::class);
        $accountRepository->findOneByCustomerAndChannel($customer, $channel)->willReturn($account);

        $transactionRepository = $this->prophesize(LoyaltyTransactionRepositoryInterface::class);
        $transactionRepository->findLatestByAccount($account, 50)->willReturn([$transaction])->shouldBeCalled();
        $transactionRepository->countByAccount($account)->willReturn(1)->shouldBeCalled();

        $twig = $this->prophesize(Environment::class);
        $twig->render('@SetonoSyliusLoyaltyPlugin/shop/account/loyalty.html.twig', Argument::allOf(
            Argument::withEntry('account', $account),
            Argument::withEntry('total_transactions', 1),
        ))->willReturn('<html>account</html>')->shouldBeCalled();

        $action = new ShowLoyaltyAccountAction($shopperContext->reveal(), $accountRepository->reveal(), $transactionRepository->reveal(), $twig->reveal());

        $response = $action();

        self::assertSame('<html>account</html>', (string) $response->getContent());
    }

    /**
     * @test
     */
    public function it_renders_a_zero_state_when_the_customer_has_no_account(): void
    {
        $customer = $this->prophesize(CustomerInterface::class)->reveal();
        $channel = $this->prophesize(ChannelInterface::class)->reveal();

        $shopperContext = $this->prophesize(ShopperContextInterface::class);
        $shopperContext->getCustomer()->willReturn($customer);
        $shopperContext->getChannel()->willReturn($channel);

        $accountRepository = $this->prophesize(LoyaltyAccountRepositoryInterface::class);
        $accountRepository->findOneByCustomerAndChannel($customer, $channel)->willReturn(null);

        $transactionRepository = $this->prophesize(LoyaltyTransactionRepositoryInterface::class);
        $transactionRepository->findLatestByAccount(Argument::cetera())->shouldNotBeCalled();
        $transactionRepository->countByAccount(Argument::any())->shouldNotBeCalled();

        $twig = $this->prophesize(Environment::class);
        $twig->render('@SetonoSyliusLoyaltyPlugin/shop/account/loyalty.html.twig', Argument::allOf(
            Argument::withEntry('account', null),
            Argument::withEntry('total_transactions', 0),
        ))->willReturn('<html>zero</html>')->shouldBeCalled();

        $action = new ShowLoyaltyAccountAction($shopperContext->reveal(), $accountRepository->reveal(), $transactionRepository->reveal(), $twig->reveal());

        $response = $action();

        self::assertSame('<html>zero</html>', (string) $response->getContent());
    }
}
