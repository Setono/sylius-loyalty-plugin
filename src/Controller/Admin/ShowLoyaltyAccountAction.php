<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Controller\Admin;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Replay\LotReplayer;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

/**
 * The admin ledger inspector for a single loyalty account: the account summary, the FIFO lot state
 * derived by replaying the ledger (open lots with their remaining points and expiry), an invariant
 * check that the replay-derived balance matches the cached balance, and the full ledger history.
 */
final class ShowLoyaltyAccountAction
{
    public function __construct(
        private readonly LoyaltyAccountRepositoryInterface $accountRepository,
        private readonly LoyaltyTransactionRepositoryInterface $transactionRepository,
        private readonly LotReplayer $lotReplayer,
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(int $id): Response
    {
        $account = $this->accountRepository->find($id);
        if (!$account instanceof LoyaltyAccountInterface) {
            throw new NotFoundHttpException(sprintf('Loyalty account "%d" does not exist.', $id));
        }

        // findByAccount is chronological (oldest first), which is what the replay needs; the ledger
        // table is shown newest first.
        $transactions = $this->transactionRepository->findByAccount($account);
        $replay = $this->lotReplayer->replay($transactions);

        return new Response($this->twig->render('@SetonoSyliusLoyaltyPlugin/admin/loyalty_account/show.html.twig', [
            'account' => $account,
            'transactions' => array_reverse($transactions),
            'open_lots' => $replay->getOpenLots(),
            'replay_balance' => $replay->getBalance(),
            'deficit' => $replay->getDeficit(),
            'consistent' => $replay->getBalance() === $account->getBalance(),
        ]));
    }
}
