<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Controller\Action\Shop;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyOrderInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Sets the customer's redemption intent on the cart. "Use max" writes the entire current
 * balance as the request — the applied amount clamps to the cart as always and grows with the
 * cart automatically, without re-tapping.
 */
final class ApplyRedemptionAction
{
    public const CSRF_TOKEN_ID = 'setono_sylius_loyalty_redemption';

    public function __construct(
        private readonly CartContextInterface $cartContext,
        private readonly LoyaltyAccountRepositoryInterface $accountRepository,
        private readonly LoyaltyProgramProviderInterface $programProvider,
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
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $token))) {
            $this->flash($request, 'error', 'setono_sylius_loyalty.redemption_invalid_request');

            return $redirect;
        }

        $cart = $this->cartContext->getCart();
        $customer = $cart instanceof OrderInterface ? $cart->getCustomer() : null;
        $channel = $cart instanceof OrderInterface ? $cart->getChannel() : null;

        if (!$cart instanceof OrderInterface ||
            !$cart instanceof LoyaltyOrderInterface ||
            !$customer instanceof CustomerInterface ||
            null === $channel
        ) {
            $this->flash($request, 'error', 'setono_sylius_loyalty.redemption_not_available');

            return $redirect;
        }

        $account = $this->accountRepository->findOneByCustomerAndChannel($customer, $channel);
        if (null === $account || !$account->isEnabled()) {
            $this->flash($request, 'error', 'setono_sylius_loyalty.redemption_not_available');

            return $redirect;
        }

        $points = $request->request->getBoolean('use_max')
            ? $account->getBalance()
            : (int) $request->request->get('points');

        $program = $this->programProvider->getByChannel($channel);

        if ($points < $program->getMinRedeemPoints() || $points > $account->getBalance()) {
            $this->flash($request, 'error', 'setono_sylius_loyalty.redemption_invalid_points');

            return $redirect;
        }

        $cart->setLoyaltyPointsRequested($points);
        $this->orderProcessor->process($cart);
        $this->entityManager->flush();

        $this->flash($request, 'success', 'setono_sylius_loyalty.redemption_applied');

        return $redirect;
    }

    private function flash(Request $request, string $type, string $message): void
    {
        $session = $request->getSession();
        if ($session instanceof Session) {
            $session->getFlashBag()->add($type, $message);
        }
    }
}
