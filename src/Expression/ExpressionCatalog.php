<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression;

use Setono\SyliusLoyaltyPlugin\Expression\Function\ExpressionFunctionRegistryInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;

final class ExpressionCatalog implements ExpressionCatalogInterface
{
    /**
     * @var array<string, array{type: string, description: string, triggers: list<string>|null}>
     */
    private const VARIABLES = [
        'order' => [
            'type' => 'order',
            'description' => 'setono_sylius_loyalty.expression.variable.order',
            'triggers' => [EarningRuleInterface::TRIGGER_ORDER_ELIGIBLE],
        ],
        'basis' => [
            'type' => 'int',
            'description' => 'setono_sylius_loyalty.expression.variable.basis',
            'triggers' => [EarningRuleInterface::TRIGGER_ORDER_ELIGIBLE],
        ],
        'customer' => [
            'type' => 'customer',
            'description' => 'setono_sylius_loyalty.expression.variable.customer',
            'triggers' => null,
        ],
        'account' => [
            'type' => 'account',
            'description' => 'setono_sylius_loyalty.expression.variable.account',
            'triggers' => null,
        ],
        'channel' => [
            'type' => 'channel',
            'description' => 'setono_sylius_loyalty.expression.variable.channel',
            'triggers' => null,
        ],
        'context' => [
            'type' => 'context',
            'description' => 'setono_sylius_loyalty.expression.variable.context',
            'triggers' => null,
        ],
    ];

    /**
     * @var array<string, array<string, string>>
     */
    private const TYPES = [
        'order' => [
            'total' => 'int',
            'itemsTotal' => 'int',
            'shippingTotal' => 'int',
            'number' => 'string',
            'currencyCode' => 'string',
            'customer' => 'customer',
            'channel' => 'channel',
        ],
        'customer' => [
            'email' => 'string',
            'firstName' => 'string',
            'lastName' => 'string',
            'group' => 'customer_group',
        ],
        'customer_group' => [
            'code' => 'string',
            'name' => 'string',
        ],
        'account' => [
            'balance' => 'int',
            'lifetimeEarned' => 'int',
            'enabled' => 'bool',
        ],
        'channel' => [
            'code' => 'string',
            'name' => 'string',
        ],
    ];

    public function __construct(
        private readonly ExpressionFunctionRegistryInterface $functions,
    ) {
    }

    public function getVariables(?string $trigger = null): array
    {
        if (null === $trigger) {
            return self::VARIABLES;
        }

        return array_filter(
            self::VARIABLES,
            static fn (array $variable): bool => null === $variable['triggers'] || in_array($trigger, $variable['triggers'], true),
        );
    }

    public function getTypeMembers(string $type): array
    {
        return self::TYPES[$type] ?? [];
    }

    public function toArray(): array
    {
        $functions = [];
        foreach ($this->functions->all() as $function) {
            $functions[] = [
                'name' => $function->getName(),
                'signature' => $function->getSignature(),
                'description' => $function->getDescription(),
            ];
        }

        return [
            'variables' => self::VARIABLES,
            'types' => self::TYPES,
            'functions' => $functions,
        ];
    }
}
