<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Controller\Action\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Enables/disables a loyalty account. A disabled account earns nothing and cannot redeem;
 * manual adjustments remain allowed and expiry still runs.
 */
final class ToggleAccountAction
{
    /**
     * @param class-string<LoyaltyAccountInterface> $accountClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $accountClass,
    ) {
    }

    public function __invoke(Request $request, int $id): Response
    {
        $account = $this->entityManager->find($this->accountClass, $id);
        if (!$account instanceof LoyaltyAccountInterface) {
            throw new NotFoundHttpException('Loyalty account not found');
        }

        $token = (string) $request->request->get('_csrf_token');
        if ($this->csrfTokenManager->isTokenValid(new CsrfToken('setono_sylius_loyalty_admin', $token))) {
            $account->setEnabled(!$account->isEnabled());
            $this->entityManager->flush();

            $session = $request->getSession();
            if ($session instanceof Session) {
                $session->getFlashBag()->add('success', $account->isEnabled()
                    ? 'setono_sylius_loyalty.account_enabled'
                    : 'setono_sylius_loyalty.account_disabled');
            }
        }

        return new RedirectResponse($this->urlGenerator->generate('setono_sylius_loyalty_admin_account_inspect', ['id' => $id]));
    }
}
