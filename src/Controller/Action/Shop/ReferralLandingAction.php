<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Controller\Action\Shop;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Referral\AttributionCookie;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The referral share URL /r/{code}: stores the attribution cookie (last click wins) and
 * redirects to the homepage.
 */
final class ReferralLandingAction
{
    /**
     * @param RepositoryInterface<LoyaltyAccountInterface> $accountRepository
     */
    public function __construct(
        private readonly RepositoryInterface $accountRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request, string $code): Response
    {
        $response = new RedirectResponse($this->urlGenerator->generate('sylius_shop_homepage'));

        $code = strtoupper($code);
        if (AttributionCookie::isValidFormat($code) && null !== $this->accountRepository->findOneBy(['referralCode' => $code])) {
            $response->headers->setCookie(AttributionCookie::create($code));
        }

        return $response;
    }
}
