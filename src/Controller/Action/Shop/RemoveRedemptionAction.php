<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Controller\Action\Shop;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyOrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class RemoveRedemptionAction
{
    public function __construct(
        private readonly CartContextInterface $cartContext,
        private readonly OrderProcessorInterface $orderProcessor,
        private readonly EntityManagerInterface $entityManager,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $redirect = new RedirectResponse($this->urlGenerator->generate('sylius_shop_cart_summary'));

        $token = (string) $request->request->get('_csrf_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(ApplyRedemptionAction::CSRF_TOKEN_ID, $token))) {
            return $redirect;
        }

        $cart = $this->cartContext->getCart();
        if ($cart instanceof LoyaltyOrderInterface) {
            $cart->setLoyaltyPointsRequested(null);
            $this->orderProcessor->process($cart);
            $this->entityManager->flush();

            $session = $request->getSession();
            if ($session instanceof Session) {
                $session->getFlashBag()->add('success', 'setono_sylius_loyalty.redemption_removed');
            }
        }

        return $redirect;
    }
}
