<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Referral\FraudCheck;

final class FraudFlag
{
    public function __construct(
        public readonly string $check,
        public readonly ?string $detail = null,
    ) {
    }

    /**
     * @return array{check: string, detail: string|null}
     */
    public function toArray(): array
    {
        return ['check' => $this->check, 'detail' => $this->detail];
    }
}
