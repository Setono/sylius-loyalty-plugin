<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EventListener\Doctrine;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * Adds custom transaction types to the loyalty transaction's Doctrine discriminator map:
 * any Sylius-registered resource model extending the transaction root is picked up
 * automatically, with the discriminator value coming from the class's own
 * getDiscriminator(). Plugin users add a type by subclassing and registering the class as a
 * resource — no plugin-specific configuration.
 */
final class DiscriminatorMapListener
{
    /**
     * @param class-string $transactionClass
     * @param array<string, array{classes?: array{model?: class-string}}> $resources the
     *        %sylius.resources% parameter
     */
    public function __construct(
        private readonly string $transactionClass,
        private readonly array $resources,
    ) {
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $metadata = $eventArgs->getClassMetadata();

        if ($metadata->getName() !== $this->transactionClass) {
            return;
        }

        foreach ($this->resources as $resource) {
            $model = $resource['classes']['model'] ?? null;
            if (null === $model || !is_subclass_of($model, $this->transactionClass)) {
                continue;
            }

            $reflection = new \ReflectionClass($model);
            if ($reflection->isAbstract() || in_array($model, $metadata->discriminatorMap, true)) {
                continue;
            }

            /** @var callable(): string $discriminator */
            $discriminator = [$model, 'getDiscriminator'];

            $metadata->addDiscriminatorMapClass($discriminator(), $model);
        }
    }
}
