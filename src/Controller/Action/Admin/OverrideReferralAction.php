<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Controller\Action\Admin;

use Setono\SyliusLoyaltyPlugin\Model\ReferralInterface;
use Setono\SyliusLoyaltyPlugin\Referral\ReferralQualifierInterface;
use Setono\SyliusLoyaltyPlugin\Repository\ReferralRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Manual status override: an admin can requalify a fraud-rejected referral. Requalification
 * re-runs the reward path against the recorded first order.
 */
final class OverrideReferralAction
{
    public function __construct(
        private readonly ReferralRepositoryInterface $referralRepository,
        private readonly ReferralQualifierInterface $referralQualifier,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request, int $id): Response
    {
        $referral = $this->referralRepository->find($id);
        if (!$referral instanceof ReferralInterface) {
            throw new NotFoundHttpException();
        }

        $response = new RedirectResponse($this->urlGenerator->generate('setono_sylius_loyalty_admin_referral_index'));

        $session = $request->getSession();
        \assert($session instanceof Session);

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('setono_sylius_loyalty_admin', (string) $request->request->get('_csrf_token')))) {
            $session->getFlashBag()->add('error', 'setono_sylius_loyalty.referral_override_invalid');

            return $response;
        }

        if (ReferralInterface::STATUS_REJECTED !== $referral->getStatus()) {
            $session->getFlashBag()->add('error', 'setono_sylius_loyalty.referral_override_not_rejected');

            return $response;
        }

        $referral->setFraudFlags([]);
        $this->referralQualifier->requalify($referral);

        $session->getFlashBag()->add('success', 'setono_sylius_loyalty.referral_overridden');

        return $response;
    }
}
