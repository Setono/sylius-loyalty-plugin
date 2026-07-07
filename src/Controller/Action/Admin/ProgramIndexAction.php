<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Controller\Action\Admin;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Lists each channel's loyalty program (creating missing ones with defaults on first access)
 * with links to the per-channel settings form.
 *
 * Deliberately not the ResourceController index action: a resource grid can only list
 * program rows that exist, while this page must surface every channel — including ones
 * whose program has not been lazily created yet — so admins can configure a brand-new
 * channel before any loyalty activity happens on it.
 */
final class ProgramIndexAction
{
    /**
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(): Response
    {
        /** @var list<array{channel: ChannelInterface, program: LoyaltyProgramInterface}> $programs */
        $programs = [];
        foreach ($this->channelRepository->findAll() as $channel) {
            if ($channel instanceof ChannelInterface) {
                $programs[] = [
                    'channel' => $channel,
                    'program' => $this->programProvider->getByChannel($channel),
                ];
            }
        }

        return new Response($this->twig->render('@SetonoSyliusLoyaltyPlugin/admin/program/index.html.twig', [
            'programs' => $programs,
        ]));
    }
}
