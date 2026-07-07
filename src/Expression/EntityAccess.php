<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression;

/**
 * Transparent getter access for expressions: ExpressionLanguage's dot syntax only reads
 * public properties, while entities expose getters — this generic bridge forwards
 * "customer.email" to getEmail()/isEmail()/hasEmail() and wraps returned objects so chains
 * like "order.customer.group.code" keep working. Nothing is curated here: the catalog
 * whitelist is enforced at save and lint time by the validator.
 */
final class EntityAccess
{
    public function __construct(
        private readonly object $object,
    ) {
    }

    public function __get(string $name): mixed
    {
        foreach (['get', 'is', 'has'] as $prefix) {
            $method = $prefix . ucfirst($name);
            if (method_exists($this->object, $method)) {
                $value = (new \ReflectionMethod($this->object, $method))->invoke($this->object);

                return is_object($value) && !$value instanceof \DateTimeInterface ? new self($value) : $value;
            }
        }

        $properties = get_object_vars($this->object);
        if (array_key_exists($name, $properties)) {
            return $properties[$name];
        }

        throw new \RuntimeException(sprintf('Neither a getter nor a public property "%s" exists on "%s".', $name, $this->object::class));
    }

    public function __isset(string $name): bool
    {
        foreach (['get', 'is', 'has'] as $prefix) {
            if (method_exists($this->object, $prefix . ucfirst($name))) {
                return true;
            }
        }

        return property_exists($this->object, $name);
    }
}
