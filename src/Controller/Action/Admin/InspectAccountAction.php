<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Controller\Action\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Inspector\AccountInspectorInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

/**
 * The admin ledger inspector: the full ledger with replay-derived lot states and the
 * per-account invariant check — the answer to "this customer says their points are wrong"
 * without leaving admin.
 */
final class InspectAccountAction
{
    /**
     * @param class-string<LoyaltyAccountInterface> $accountClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AccountInspectorInterface $accountInspector,
        private readonly LoyaltyTransactionRepositoryInterface $transactionRepository,
        private readonly Environment $twig,
        private readonly string $accountClass,
        /** @var list<string> */
        private readonly array $manualAdjustmentReasons,
    ) {
    }

    public function __invoke(int $id): Response
    {
        $account = $this->entityManager->find($this->accountClass, $id);
        if (!$account instanceof LoyaltyAccountInterface) {
            throw new NotFoundHttpException('Loyalty account not found');
        }

        return new Response($this->twig->render('@SetonoSyliusLoyaltyPlugin/Admin/Account/inspect.html.twig', [
            'account' => $account,
            'inspection' => $this->accountInspector->inspect($account),
            'transactions' => array_reverse($this->transactionRepository->findForReplay($account)),
            'reasons' => $this->manualAdjustmentReasons,
        ]));
    }
}
