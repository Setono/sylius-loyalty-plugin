<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Inspector;

use Setono\SyliusLoyaltyPlugin\Ledger\ReplayResult;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;

/**
 * The replay-derived state of an account plus its invariant check — the answer to "this
 * customer says their points are wrong".
 */
final class AccountInspection
{
    /**
     * @param list<string> $errors ledger corruption (never auto-fixed)
     * @param list<string> $warnings expected operational conditions, e.g. overdue open lots
     *        when the expiry cron is behind or a listener deferred an expiration
     */
    public function __construct(
        public readonly LoyaltyAccountInterface $account,
        public readonly ReplayResult $replay,
        public readonly array $errors,
        public readonly array $warnings,
    ) {
    }

    public function isHealthy(): bool
    {
        return [] === $this->errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $lots = [];
        foreach ($this->replay->lots as $lotState) {
            $consumptions = [];
            foreach ($lotState->getConsumptions() as $consumption) {
                $consumptions[] = [
                    'debit' => $consumption->debit->getId(),
                    'points' => $consumption->points,
                ];
            }

            $lots[] = [
                'lot' => $lotState->lot->getId(),
                'points' => $lotState->lot->getPoints(),
                'expiresAt' => $lotState->lot->getExpiresAt()?->format(\DateTimeInterface::ATOM),
                'remaining' => $lotState->getRemaining(),
                'closedByExpiration' => $lotState->isClosedByExpiration(),
                'consumptions' => $consumptions,
            ];
        }

        return [
            'account' => [
                'id' => $this->account->getId(),
                'customer' => $this->account->getCustomer()?->getEmail(),
                'channel' => $this->account->getChannel()?->getCode(),
                'enabled' => $this->account->isEnabled(),
                'balance' => $this->account->getBalance(),
                'lifetimeEarned' => $this->account->getLifetimeEarned(),
                'derivedBalance' => $this->replay->balance,
            ],
            'lots' => $lots,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
