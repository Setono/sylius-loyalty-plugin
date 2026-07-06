<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Expression\View;

use Sylius\Component\Channel\Model\ChannelInterface;

final class ChannelView
{
    private function __construct(
        public readonly string $code,
        public readonly string $name,
    ) {
    }

    public static function fromChannel(ChannelInterface $channel): self
    {
        return new self(
            (string) $channel->getCode(),
            (string) $channel->getName(),
        );
    }
}
