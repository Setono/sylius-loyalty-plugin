<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Message\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Message\AwardOrderPoints;
use Setono\SyliusLoyaltyPlugin\Message\ClaimPastOrderPoints;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderPaymentStates;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;
use Symfony\Component\Messenger\Envelope;

/**
 * Runs the normal earning pipeline (with current rules — historical rule state is not
 * reconstructed) for each of the customer's past orders that already satisfy the award
 * moment. The one-earn-per-order unique constraint makes any overlap or redelivery a no-op.
 */
final class ClaimPastOrderPointsHandler
{
    /**
     * @param class-string $customerClass
     * @param class-string $channelClass
     * @param class-string $orderClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly MessageBusInterface $messageBus,
        private readonly string $customerClass,
        private readonly string $channelClass,
        private readonly string $orderClass,
    ) {
    }

    public function __invoke(ClaimPastOrderPoints $message): void
    {
        $customer = $this->entityManager->find($this->customerClass, $message->customerId);
        $channel = $this->entityManager->find($this->channelClass, $message->channelId);

        if (!$customer instanceof CustomerInterface || !$channel instanceof ChannelInterface) {
            return;
        }

        $program = $this->programProvider->getByChannel($channel);
        if (!$program->isRetroactiveGuestPoints()) {
            return;
        }

        foreach ($this->eligibleOrderIds($customer, $channel, $program) as $orderId) {
            $this->messageBus->dispatch(
                new Envelope(new AwardOrderPoints($orderId), [new DispatchAfterCurrentBusStamp()]),
            );
        }
    }

    /**
     * @return list<int>
     */
    private function eligibleOrderIds(
        CustomerInterface $customer,
        ChannelInterface $channel,
        LoyaltyProgramInterface $program,
    ): array {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('o.id')
            ->from($this->orderClass, 'o')
            ->andWhere('o.customer = :customer')
            ->andWhere('o.channel = :channel')
            ->setParameter('customer', $customer)
            ->setParameter('channel', $channel)
        ;

        if (LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_ORDER_FULFILLED === $program->getAwardOrderPointsAt()) {
            $queryBuilder->andWhere('o.state = :state')->setParameter('state', OrderInterface::STATE_FULFILLED);
        } else {
            $queryBuilder->andWhere('o.paymentState = :paymentState')->setParameter('paymentState', OrderPaymentStates::STATE_PAID);
        }

        /** @var list<array{id: int|string}> $rows */
        $rows = $queryBuilder->getQuery()->getScalarResult();

        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }
}
