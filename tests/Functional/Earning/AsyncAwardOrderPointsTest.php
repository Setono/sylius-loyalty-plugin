<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional\Earning;

use Setono\SyliusLoyaltyPlugin\Message\AwardOrderPoints;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;

/**
 * The awarding pipeline through a real (doctrine) transport: the message serializes with just
 * the order id, survives the queue round-trip, and redeliveries stay idempotent.
 */
final class AsyncAwardOrderPointsTest extends AwardOrderPointsTestCase
{
    /**
     * @test
     */
    public function it_awards_points_once_through_an_async_transport_with_redelivery(): void
    {
        $container = self::getContainer();

        $channel = $this->channel();
        $this->rule($channel, points: 1, perAmount: 100);

        $customer = $this->customer();
        $order = $this->paidOrder($channel, $customer, unitPrice: 2500, quantity: 2); // 50.00

        $bus = $container->get(MessageBusInterface::class);
        \assert($bus instanceof MessageBusInterface);

        // Dispatch the same message twice to the transport — a redelivery
        $message = new AwardOrderPoints((int) $order->getId());
        $bus->dispatch($message, [new TransportNamesStamp('loyalty_async')]);
        $bus->dispatch($message, [new TransportNamesStamp('loyalty_async')]);

        self::assertSame(2, $this->consumeAll($bus));

        $accountProvider = $container->get(LoyaltyAccountProviderInterface::class);
        \assert($accountProvider instanceof LoyaltyAccountProviderInterface);

        self::assertSame(50, $accountProvider->getByCustomerAndChannel($customer, $channel)->getBalance());
    }

    /**
     * In-process worker: pull every queued envelope and hand it to the bus as received.
     */
    private function consumeAll(MessageBusInterface $bus): int
    {
        $receiver = self::getContainer()->get('messenger.transport.loyalty_async');
        \assert($receiver instanceof ReceiverInterface);

        $consumed = 0;
        do {
            $envelopes = [...$receiver->get()];
            foreach ($envelopes as $envelope) {
                \assert($envelope instanceof Envelope);
                $bus->dispatch($envelope->with(new ReceivedStamp('loyalty_async')));
                $receiver->ack($envelope);
                ++$consumed;
            }
        } while ([] !== $envelopes);

        return $consumed;
    }
}
