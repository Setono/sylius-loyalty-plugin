<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Controller\Action\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * The only human write path into the ledger: a signed manual adjustment with a reason code
 * (from the bundle config) and a mandatory note, audit-logged with the admin user.
 */
final class ManualAdjustmentAction
{
    /**
     * @param class-string<LoyaltyAccountInterface> $accountClass
     * @param list<string> $reasons
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoyaltyLedgerInterface $ledger,
        private readonly Security $security,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $accountClass,
        private readonly array $reasons,
    ) {
    }

    public function __invoke(Request $request, int $id): Response
    {
        $account = $this->entityManager->find($this->accountClass, $id);
        if (!$account instanceof LoyaltyAccountInterface) {
            throw new NotFoundHttpException('Loyalty account not found');
        }

        $redirect = new RedirectResponse($this->urlGenerator->generate('setono_sylius_loyalty_admin_account_inspect', ['id' => $id]));

        $token = (string) $request->request->get('_csrf_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('setono_sylius_loyalty_admin', $token))) {
            return $redirect;
        }

        $points = (int) $request->request->get('points');
        $reason = (string) $request->request->get('reason');
        $note = trim((string) $request->request->get('note'));

        if (0 === $points || '' === $note || !in_array($reason, $this->reasons, true)) {
            $this->flash($request, 'error', 'setono_sylius_loyalty.adjustment_invalid');

            return $redirect;
        }

        $adminUser = $this->security->getUser();
        $adminUser = $adminUser instanceof AdminUserInterface ? $adminUser : null;

        if ($points > 0) {
            $this->ledger->manualCredit($account, $points, $reason, $note, $adminUser);
        } else {
            $this->ledger->manualDebit($account, -$points, $reason, $note, $adminUser);
        }

        $this->flash($request, 'success', 'setono_sylius_loyalty.adjustment_applied');

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
