<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Exception;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;

final class InsufficientBalanceException extends \RuntimeException implements ExceptionInterface
{
    public static function create(LoyaltyAccountInterface $account, int $points): self
    {
        return new self(sprintf(
            'The loyalty account (id: %s) has a balance of %d points, which is insufficient to debit %d points',
            (string) ($account->getId() ?? 'unsaved'),
            $account->getBalance(),
            $points,
        ));
    }
}
