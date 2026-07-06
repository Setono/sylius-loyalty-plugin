<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Controller\Action\Admin;

use Setono\SyliusLoyaltyPlugin\Provider\Admin\DashboardStatsProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class DashboardAction
{
    public function __construct(
        private readonly DashboardStatsProviderInterface $statsProvider,
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(): Response
    {
        return new Response($this->twig->render('@SetonoSyliusLoyaltyPlugin/Admin/Dashboard/index.html.twig', [
            'stats' => $this->statsProvider->getStats(),
        ]));
    }
}
