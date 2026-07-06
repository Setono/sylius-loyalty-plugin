<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression\Function;

use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;
use Webmozart\Assert\Assert;

/**
 * The basic math functions available inside expressions (ExpressionLanguage ships none).
 */
final class MathFunction implements ExpressionFunctionInterface
{
    public const NAMES = ['floor', 'ceil', 'round', 'abs', 'min', 'max'];

    public function __construct(
        private readonly string $name,
    ) {
        Assert::oneOf($name, self::NAMES);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSignature(): string
    {
        return match ($this->name) {
            'min', 'max' => sprintf('%s(...values: number): number', $this->name),
            default => sprintf('%s(value: number): number', $this->name),
        };
    }

    public function getDescription(): string
    {
        return sprintf('setono_sylius_loyalty.expression.function.%s', $this->name);
    }

    public function evaluate(EarningContext $context, mixed ...$arguments): mixed
    {
        $numbers = array_map(
            static fn (mixed $argument): float => is_numeric($argument) ? (float) $argument : 0.0,
            array_values($arguments),
        );

        if ([] === $numbers) {
            return 0.0;
        }

        return match ($this->name) {
            'floor' => floor($numbers[0]),
            'ceil' => ceil($numbers[0]),
            'round' => round($numbers[0]),
            'abs' => abs($numbers[0]),
            'min' => min($numbers),
            'max' => max($numbers),
            default => 0.0,
        };
    }
}
