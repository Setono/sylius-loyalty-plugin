<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EventListener\Doctrine;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * Merges custom transaction types (registered via the "setono_sylius_loyalty.transaction_types"
 * bundle config) into the loyalty transaction's Doctrine discriminator map, so plugin users can
 * add their own ledger transaction types without touching the plugin's mapping.
 */
final class DiscriminatorMapListener
{
    /**
     * @param class-string $transactionClass
     * @param array<string, class-string> $transactionTypes
     */
    public function __construct(
        private readonly string $transactionClass,
        private readonly array $transactionTypes,
    ) {
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $metadata = $eventArgs->getClassMetadata();

        if ($metadata->getName() !== $this->transactionClass) {
            return;
        }

        foreach ($this->transactionTypes as $type => $class) {
            $metadata->addDiscriminatorMapClass($type, $class);
        }
    }
}
