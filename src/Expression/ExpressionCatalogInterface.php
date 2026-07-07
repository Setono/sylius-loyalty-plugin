<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression;

/**
 * The typed whitelist behind expression mode. One declaration simultaneously drives the
 * runtime sandbox (validation rejects anything outside it), the editor's autocompletion (it is
 * serialized into the form's data-catalog attribute), and the reference panel — so completion
 * can never suggest a path the validator rejects, and vice versa.
 */
interface ExpressionCatalogInterface
{
    /**
     * The variables available to expressions, optionally narrowed to a trigger (the
     * order/basis variables exist only under the built-in order trigger).
     *
     * @return array<string, array{type: string, description: string, triggers: list<string>|null}>
     */
    public function getVariables(?string $trigger = null): array;

    /**
     * The members of a catalog type: member name => type name (a scalar type or another
     * catalog type).
     *
     * @return array<string, string>
     */
    public function getTypeMembers(string $type): array;

    /**
     * The serializable form consumed by the expression editor.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
