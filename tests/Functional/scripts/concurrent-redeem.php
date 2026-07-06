<?php

/*
 * Child process of the concurrent redemption test: redeems the given points on the given
 * order and reports the outcome on stdout. Runs with a real database connection.
 */

declare(strict_types=1);

use Setono\SyliusLoyaltyPlugin\Exception\InsufficientBalanceException;
use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Tests\Application\Kernel;
use Sylius\Component\Core\Model\OrderInterface;

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';

require dirname(__DIR__, 2) . '/Application/config/bootstrap.php';

$orderId = (int) ($argv[1] ?? 0);
$points = (int) ($argv[2] ?? 0);

$kernel = new Kernel('test', false);
$kernel->boot();
$container = $kernel->getContainer()->get('test.service_container');
assert($container instanceof \Symfony\Component\DependencyInjection\ContainerInterface);

$entityManager = $container->get('doctrine.orm.entity_manager');
assert($entityManager instanceof \Doctrine\ORM\EntityManagerInterface);

$orderClass = $container->getParameter('sylius.model.order.class');
assert(is_string($orderClass) && class_exists($orderClass));

$order = $entityManager->find($orderClass, $orderId);

if (!$order instanceof OrderInterface) {
    echo 'ORDER_NOT_FOUND';
    exit(2);
}

$ledger = $container->get(LoyaltyLedgerInterface::class);
assert($ledger instanceof LoyaltyLedgerInterface);

try {
    $transaction = $ledger->redeem($order, $points);
    echo null === $transaction ? 'NOOP' : 'OK';
} catch (InsufficientBalanceException) {
    echo 'INSUFFICIENT';
}
