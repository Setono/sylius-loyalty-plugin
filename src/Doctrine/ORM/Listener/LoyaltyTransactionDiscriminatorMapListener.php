<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Doctrine\ORM\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransaction;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;

/**
 * Builds the LoyaltyTransaction single-table-inheritance discriminator map at runtime from the
 * registered Sylius resources: every non-abstract resource whose model implements
 * {@see LoyaltyTransactionInterface} is added, keyed by its static {@see LoyaltyTransactionInterface::getType()}.
 *
 * A project can therefore add its own transaction type simply by registering it as a resource.
 *
 * @internal
 */
final class LoyaltyTransactionDiscriminatorMapListener
{
    /** @var array<string, class-string<LoyaltyTransactionInterface>> */
    private readonly array $discriminatorMap;

    /**
     * @param array<string, array{classes?: array{model?: mixed}}> $resources the `sylius.resources` parameter
     */
    public function __construct(array $resources)
    {
        $map = [];
        foreach ($resources as $resource) {
            $model = $resource['classes']['model'] ?? null;
            if (!is_string($model) || !is_a($model, LoyaltyTransactionInterface::class, true)) {
                continue;
            }

            if ((new \ReflectionClass($model))->isAbstract()) {
                continue;
            }

            $map[$model::getType()] = $model;
        }

        $this->discriminatorMap = $map;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        $metadata = $event->getClassMetadata();

        if (LoyaltyTransaction::class !== $metadata->getName()) {
            return;
        }

        // Reset the map Doctrine auto-generates from the subclass short names, then set ours
        // (setDiscriminatorMap appends rather than replaces).
        $metadata->discriminatorMap = [];
        $metadata->subClasses = [];
        $metadata->setDiscriminatorMap($this->discriminatorMap);
    }
}
