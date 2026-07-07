<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression\Function;

use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;

/**
 * A domain function available inside expressions. Implementations are registered with the
 * "setono_sylius_loyalty.expression_function" tag (autoconfigured); the metadata feeds the
 * expression editor's autocompletion and the generated reference panel, so custom functions
 * appear in both automatically.
 */
interface ExpressionFunctionInterface
{
    public function getName(): string;

    /**
     * E.g. "taxon_total(taxonCode: string): int".
     */
    public function getSignature(): string;

    /**
     * A translation key describing the function.
     */
    public function getDescription(): string;

    public function evaluate(EarningContext $context, mixed ...$arguments): mixed;
}
