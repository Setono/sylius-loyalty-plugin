<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\ManualLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Loyalty data is personal data. On customer deletion the accounts and their ledgers are
 * deleted (the default — historical channel aggregates shift accordingly, which is accepted),
 * or — when retain_anonymized_ledger is enabled — de-identified: the account keeps an opaque
 * token instead of the customer link, and ledger rows keep type, signed points, dates, and
 * channel while order references, source identifiers, notes, and admin references are nulled.
 * This is the single, deliberate exception to the ledger's append-only rule.
 */
final class CustomerDeletionListener
{
    /**
     * @param class-string<LoyaltyAccountInterface> $accountClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoyaltyTransactionRepositoryInterface $transactionRepository,
        private readonly bool $retainAnonymizedLedger,
        private readonly string $accountClass,
    ) {
    }

    public function __invoke(GenericEvent $event): void
    {
        $customer = $event->getSubject();
        if (!$customer instanceof CustomerInterface) {
            return;
        }

        /** @var list<LoyaltyAccountInterface> $accounts */
        $accounts = $this->entityManager->getRepository($this->accountClass)->findBy(['customer' => $customer]);

        foreach ($accounts as $account) {
            if ($this->retainAnonymizedLedger) {
                $this->anonymize($account);
            } else {
                $this->entityManager->remove($account);
            }
        }
    }

    private function anonymize(LoyaltyAccountInterface $account): void
    {
        $account->setCustomer(null);
        $account->setAnonymizedToken(bin2hex(random_bytes(16)));
        $account->setReferralCode(null);
        $account->setEnabled(false);

        foreach ($this->transactionRepository->findForReplay($account) as $transaction) {
            if ($transaction instanceof EarnOrderLoyaltyTransactionInterface || $transaction instanceof RedeemLoyaltyTransactionInterface) {
                $transaction->setOrder(null);
            }

            if ($transaction instanceof EarnActionLoyaltyTransactionInterface) {
                $transaction->setSourceIdentifier(null);
            }

            if ($transaction instanceof ManualLoyaltyTransactionInterface) {
                $transaction->setNote(null);
                $transaction->setAdminUser(null);
            }
        }
    }
}
