<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional\Redemption;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Tests\Application\Entity\Order;
use Setono\SyliusLoyaltyPlugin\Tests\Functional\FunctionalTestCase;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Symfony\Component\Process\Process;

/**
 * Two parallel processes may not overspend a balance. This test needs real commits (the
 * child processes have their own connections), so it opts out of dama's transaction wrapping
 * and cleans up after itself.
 *
 * @group commits
 */
final class ConcurrentRedemptionTest extends FunctionalTestCase
{
    private ?CustomerInterface $customer = null;

    public static function setUpBeforeClass(): void
    {
        StaticDriver::setKeepStaticConnections(false);
    }

    public static function tearDownAfterClass(): void
    {
        StaticDriver::setKeepStaticConnections(true);
    }

    protected function tearDown(): void
    {
        // The account and its ledger cascade with the customer; orders must go first
        if (null !== $this->customer) {
            $entityManager = $this->entityManager();
            $customer = $entityManager->find($this->customer::class, $this->customer->getId());
            if (null !== $customer) {
                foreach ($entityManager->getRepository(Order::class)->findBy(['customer' => $customer]) as $order) {
                    $entityManager->remove($order);
                }
                $entityManager->remove($customer);
                $entityManager->flush();
            }
        }

        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_never_overspends_a_balance_under_concurrent_redemptions(): void
    {
        $container = self::getContainer();
        $entityManager = $this->entityManager();

        $channel = $entityManager->getRepository(\Sylius\Component\Core\Model\Channel::class)->findOneByCode('FASHION_WEB');
        \assert($channel instanceof ChannelInterface);

        $this->customer = $this->customer();

        $accountProvider = $container->get(LoyaltyAccountProviderInterface::class);
        \assert($accountProvider instanceof LoyaltyAccountProviderInterface);
        $account = $accountProvider->getByCustomerAndChannel($this->customer, $channel);

        $ledger = $container->get(LoyaltyLedgerInterface::class);
        \assert($ledger instanceof LoyaltyLedgerInterface);
        $ledger->manualCredit($account, 500, 'goodwill', 'Concurrency seed');

        $firstOrder = $this->order($channel, $this->customer);
        $secondOrder = $this->order($channel, $this->customer);

        $script = dirname(__DIR__) . '/scripts/concurrent-redeem.php';
        $first = new Process([\PHP_BINARY, $script, (string) $firstOrder->getId(), '400']);
        $second = new Process([\PHP_BINARY, $script, (string) $secondOrder->getId(), '400']);

        $first->start();
        $second->start();
        $first->wait();
        $second->wait();

        $outputs = [trim($first->getOutput()), trim($second->getOutput())];
        sort($outputs);

        self::assertSame(['INSUFFICIENT', 'OK'], $outputs, sprintf(
            'Expected exactly one successful redemption, got [%s] / [%s] (stderr: %s / %s)',
            $first->getOutput(),
            $second->getOutput(),
            $first->getErrorOutput(),
            $second->getErrorOutput(),
        ));

        $entityManager->clear();
        $reloaded = $entityManager->find($account::class, $account->getId());
        \assert($reloaded instanceof LoyaltyAccountInterface);

        self::assertSame(100, $reloaded->getBalance());
        self::assertGreaterThanOrEqual(0, $reloaded->getBalance());
    }

    private function customer(): CustomerInterface
    {
        $customerFactory = self::getContainer()->get('sylius.factory.customer');
        \assert(is_object($customerFactory) && method_exists($customerFactory, 'createNew'));

        $customer = $customerFactory->createNew();
        \assert($customer instanceof CustomerInterface);
        $customer->setEmail(sprintf('concurrency-%s@example.com', uniqid()));

        $this->entityManager()->persist($customer);
        $this->entityManager()->flush();

        return $customer;
    }

    private function order(ChannelInterface $channel, CustomerInterface $customer): Order
    {
        $container = self::getContainer();
        $entityManager = $this->entityManager();

        $variant = $entityManager->getRepository(\Sylius\Component\Core\Model\ProductVariant::class)->findOneBy([]);
        \assert($variant instanceof ProductVariantInterface);

        $orderFactory = $container->get('sylius.factory.order');
        \assert(is_object($orderFactory) && method_exists($orderFactory, 'createNew'));
        $order = $orderFactory->createNew();
        \assert($order instanceof Order);

        $order->setChannel($channel);
        $order->setCustomer($customer);
        $order->setCurrencyCode((string) $channel->getBaseCurrency()?->getCode());
        $order->setLocaleCode((string) $channel->getDefaultLocale()?->getCode());

        $orderItemFactory = $container->get('sylius.factory.order_item');
        \assert(is_object($orderItemFactory) && method_exists($orderItemFactory, 'createNew'));
        $item = $orderItemFactory->createNew();
        \assert($item instanceof OrderItemInterface);
        $item->setVariant($variant);

        $quantityModifier = $container->get('sylius.order_item_quantity_modifier');
        \assert($quantityModifier instanceof OrderItemQuantityModifierInterface);
        $quantityModifier->modify($item, 1);
        $item->setUnitPrice(100000);

        $order->addItem($item);

        $entityManager->persist($order);
        $entityManager->flush();

        return $order;
    }
}
