<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EventListener;

use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\SyliusLoyaltyPlugin\Event\Trigger\CustomerRegisteredTriggerEvent;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Dogfoods the trigger mechanism: fires the built-in customer_registered trigger on Sylius'
 * registration event.
 */
final class DispatchCustomerRegisteredTrigger
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(GenericEvent $event): void
    {
        $customer = $event->getSubject();
        if (!$customer instanceof CustomerInterface) {
            return;
        }

        $this->eventDispatcher->dispatch(new CustomerRegisteredTriggerEvent($customer));
    }
}
