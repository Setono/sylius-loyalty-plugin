<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression;

use Setono\SyliusLoyaltyPlugin\Exception\InvalidExpressionException;
use Setono\SyliusLoyaltyPlugin\Expression\Function\ExpressionFunctionRegistryInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\Node\GetAttrNode;
use Symfony\Component\ExpressionLanguage\Node\NameNode;
use Symfony\Component\ExpressionLanguage\Node\Node;
use Symfony\Component\ExpressionLanguage\SyntaxError;

final class ExpressionValidator implements ExpressionValidatorInterface
{
    private const SCALAR_TYPES = ['int', 'float', 'string', 'bool'];

    private ?ExpressionLanguage $expressionLanguage = null;

    public function __construct(
        private readonly ExpressionCatalogInterface $catalog,
        private readonly ExpressionFunctionRegistryInterface $functions,
    ) {
    }

    public function validate(string $expression, ?string $trigger = null): void
    {
        $variables = $this->catalog->getVariables($trigger);

        try {
            $parsed = $this->expressionLanguage()->parse($expression, array_keys($variables));
        } catch (SyntaxError $e) {
            throw new InvalidExpressionException($e->getMessage(), 0, $e);
        }

        $this->walk($parsed->getNodes(), $variables);
    }

    /**
     * @param array<string, array{type: string, description: string, triggers: list<string>|null}> $variables
     */
    private function walk(Node $node, array $variables): void
    {
        if ($node instanceof GetAttrNode) {
            $this->validateAttributeAccess($node, $variables);
        }

        foreach (self::children($node) as $child) {
            $this->walk($child, $variables);
        }
    }

    /**
     * @param array<string, array{type: string, description: string, triggers: list<string>|null}> $variables
     */
    private function validateAttributeAccess(GetAttrNode $node, array $variables): void
    {
        $type = self::attribute($node, 'type');

        if (GetAttrNode::METHOD_CALL === $type) {
            throw new InvalidExpressionException('Method calls are not available in expressions');
        }

        if (GetAttrNode::ARRAY_CALL === $type) {
            throw new InvalidExpressionException('Array access is not available in expressions; use dotted property access');
        }

        // Resolving the type validates the whole chain
        $this->resolveType($node, $variables);
    }

    /**
     * Resolves the catalog type of a node in a dotted chain. Returns null for the dynamic
     * trigger context, whose members are only known at dispatch time.
     *
     * @param array<string, array{type: string, description: string, triggers: list<string>|null}> $variables
     */
    private function resolveType(Node $node, array $variables): ?string
    {
        if ($node instanceof NameNode) {
            $name = self::attribute($node, 'name');
            if (!is_string($name) || !isset($variables[$name])) {
                throw new InvalidExpressionException(sprintf('Unknown variable "%s"', is_string($name) ? $name : ''));
            }

            return $variables[$name]['type'];
        }

        if ($node instanceof GetAttrNode) {
            $parent = self::children($node)['node'] ?? null;
            if (null === $parent) {
                throw new InvalidExpressionException('Property access is only available on catalog variables');
            }

            $parentType = $this->resolveType($parent, $variables);
            if (null === $parentType) {
                // Dynamic context members cannot be checked statically
                return null;
            }

            $attributeNode = self::children($node)['attribute'] ?? null;
            $attribute = $attributeNode instanceof ConstantNode ? self::attribute($attributeNode, 'value') : null;
            if (!is_string($attribute)) {
                throw new InvalidExpressionException('Dynamic property access is not available in expressions');
            }

            if ('context' === $parentType) {
                return null;
            }

            if (in_array($parentType, self::SCALAR_TYPES, true)) {
                throw new InvalidExpressionException(sprintf('Cannot access "%s" on a %s value', $attribute, $parentType));
            }

            $members = $this->catalog->getTypeMembers($parentType);
            if (!isset($members[$attribute])) {
                throw new InvalidExpressionException(sprintf('"%s" is not available on "%s"', $attribute, $parentType));
            }

            return $members[$attribute];
        }

        throw new InvalidExpressionException('Property access is only available on catalog variables');
    }

    /**
     * The Node base class declares its properties without types, so accesses are funneled
     * through these helpers.
     *
     * @return array<array-key, Node>
     */
    private static function children(Node $node): array
    {
        /** @var mixed $children */
        $children = $node->nodes;
        if (!is_array($children)) {
            return [];
        }

        return array_filter($children, static fn (mixed $child): bool => $child instanceof Node);
    }

    private static function attribute(Node $node, string $key): mixed
    {
        /** @var mixed $attributes */
        $attributes = $node->attributes;
        if (!is_array($attributes)) {
            return null;
        }

        return $attributes[$key] ?? null;
    }

    private function expressionLanguage(): ExpressionLanguage
    {
        if (null === $this->expressionLanguage) {
            $this->expressionLanguage = new ExpressionLanguage();

            foreach ($this->functions->all() as $function) {
                $this->expressionLanguage->register(
                    $function->getName(),
                    static fn (): string => '',
                    static fn (): mixed => null,
                );
            }
        }

        return $this->expressionLanguage;
    }
}
