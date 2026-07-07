<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Controller\Action\Shop;

use Setono\SyliusLoyaltyPlugin\Ledger\LotReplayerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Provider\Shop\TierProgressProviderInterface;
use Setono\SyliusLoyaltyPlugin\Referral\ReferralCodeGeneratorInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Repository\ReferralRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Twig\Environment;

/**
 * The "My loyalty" account section: balance hero, expiring-soon callout, and the flat
 * reverse-chronological transaction history with a bank-statement running balance (one
 * aggregate query for the rows newer than the page, then walking backwards within it).
 */
final class LoyaltyDashboardAction
{
    private const PAGE_SIZE = 20;

    private const EXPIRING_SOON_DAYS = 30;

    public function __construct(
        private readonly CustomerContextInterface $customerContext,
        private readonly ChannelContextInterface $channelContext,
        private readonly LoyaltyAccountRepositoryInterface $accountRepository,
        private readonly LoyaltyTransactionRepositoryInterface $transactionRepository,
        private readonly LotReplayerInterface $lotReplayer,
        private readonly TierProgressProviderInterface $tierProgressProvider,
        private readonly ReferralCodeGeneratorInterface $referralCodeGenerator,
        private readonly ReferralRepositoryInterface $referralRepository,
        private readonly \Symfony\Component\Routing\Generator\UrlGeneratorInterface $urlGenerator,
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $customer = $this->customerContext->getCustomer();
        if (!$customer instanceof CustomerInterface) {
            throw new AccessDeniedHttpException();
        }

        $account = $this->accountRepository->findOneByCustomerAndChannel($customer, $this->channelContext->getChannel());

        $page = max(1, $request->query->getInt('page', 1));
        $transactions = null === $account ? [] : $this->transactionRepository->findHistoryPage($account, $page, self::PAGE_SIZE);
        $total = null === $account ? 0 : $this->transactionRepository->countHistory($account);

        return new Response($this->twig->render('@SetonoSyliusLoyaltyPlugin/shop/account/loyalty/index.html.twig', [
            'account' => $account,
            'rows' => $this->rows($account, $transactions),
            'page' => $page,
            'pages' => (int) ceil($total / self::PAGE_SIZE),
            'expiringSoon' => $this->expiringSoon($account),
            'tierProgress' => null === $account ? null : $this->tierProgressProvider->getProgress($account),
            'referral' => null === $account ? null : $this->referralBlock($account),
        ]));
    }

    /**
     * @param list<LoyaltyTransactionInterface> $transactions
     *
     * @return list<array{transaction: LoyaltyTransactionInterface, runningBalance: int}>
     */
    private function rows(?LoyaltyAccountInterface $account, array $transactions): array
    {
        if (null === $account || [] === $transactions) {
            return [];
        }

        $runningBalance = $account->getBalance() - $this->transactionRepository->sumPointsNewerThan($account, $transactions[0]);

        $rows = [];
        foreach ($transactions as $transaction) {
            $rows[] = [
                'transaction' => $transaction,
                'runningBalance' => $runningBalance,
            ];
            $runningBalance -= $transaction->getPoints();
        }

        return $rows;
    }

    /**
     * @return array{code: string, shareUrl: string, stats: array{rewarded: int, pointsEarned: int}}
     */
    private function referralBlock(LoyaltyAccountInterface $account): array
    {
        $code = $this->referralCodeGenerator->getCode($account);

        return [
            'code' => $code,
            'shareUrl' => $this->urlGenerator->generate(
                'setono_sylius_loyalty_shop_referral_landing',
                ['code' => $code],
                \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL,
            ),
            'stats' => $this->referralRepository->getReferrerStats($account),
        ];
    }

    /**
     * @return array{points: int, until: \DateTimeImmutable}|null
     */
    private function expiringSoon(?LoyaltyAccountInterface $account): ?array
    {
        if (null === $account) {
            return null;
        }

        $now = new \DateTimeImmutable();
        $horizon = $now->modify(sprintf('+%d days', self::EXPIRING_SOON_DAYS));

        $points = 0;
        foreach ($this->lotReplayer->replay($this->transactionRepository->findForReplay($account))->getOpenLots() as $lotState) {
            $expiresAt = $lotState->lot->getExpiresAt();
            if (null !== $expiresAt && $expiresAt > $now && $expiresAt <= $horizon) {
                $points += $lotState->getRemaining();
            }
        }

        return $points > 0 ? ['points' => $points, 'until' => $horizon] : null;
    }
}
