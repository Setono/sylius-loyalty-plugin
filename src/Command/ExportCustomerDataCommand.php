<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Command;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\CreditLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnActionLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\EarnOrderLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\ManualLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Model\RedeemLoyaltyTransactionInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Repository\CustomerRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Exports a customer's loyalty data as JSON — supports GDPR access requests.
 */
#[AsCommand(
    name: 'setono:sylius-loyalty:export-customer-data',
    description: "Exports a customer's loyalty data (accounts and ledger) as JSON",
)]
final class ExportCustomerDataCommand extends Command
{
    /**
     * @param CustomerRepositoryInterface<CustomerInterface> $customerRepository
     * @param class-string<LoyaltyAccountInterface> $accountClass
     */
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly LoyaltyTransactionRepositoryInterface $transactionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $accountClass,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'The customer email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $customer = $this->customerRepository->findOneBy(['email' => (string) $input->getArgument('email')]);
        if (!$customer instanceof CustomerInterface) {
            (new SymfonyStyle($input, $output))->error('Customer not found');

            return Command::FAILURE;
        }

        /** @var list<LoyaltyAccountInterface> $accounts */
        $accounts = $this->entityManager->getRepository($this->accountClass)->findBy(['customer' => $customer]);

        $data = [
            'email' => $customer->getEmail(),
            'accounts' => array_map($this->exportAccount(...), $accounts),
        ];

        $output->writeln((string) json_encode($data, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR));

        return Command::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function exportAccount(LoyaltyAccountInterface $account): array
    {
        $transactions = [];
        foreach ($this->transactionRepository->findForReplay($account) as $transaction) {
            $discriminator = $this->entityManager->getClassMetadata($transaction::class)->discriminatorValue;

            $row = [
                'id' => $transaction->getId(),
                'type' => is_string($discriminator) ? $discriminator : $transaction::class,
                'points' => $transaction->getPoints(),
                'occurredAt' => $transaction->getOccurredAt()->format(\DateTimeInterface::ATOM),
            ];

            if ($transaction instanceof CreditLoyaltyTransactionInterface) {
                $row['expiresAt'] = $transaction->getExpiresAt()?->format(\DateTimeInterface::ATOM);
            }

            if ($transaction instanceof EarnOrderLoyaltyTransactionInterface || $transaction instanceof RedeemLoyaltyTransactionInterface) {
                $row['order'] = $transaction->getOrder()?->getNumber();
            }

            if ($transaction instanceof EarnActionLoyaltyTransactionInterface) {
                $row['sourceIdentifier'] = $transaction->getSourceIdentifier();
            }

            if ($transaction instanceof ManualLoyaltyTransactionInterface) {
                $row['reason'] = $transaction->getReason();
                $row['note'] = $transaction->getNote();
            }

            $transactions[] = $row;
        }

        return [
            'channel' => $account->getChannel()?->getCode(),
            'enabled' => $account->isEnabled(),
            'balance' => $account->getBalance(),
            'lifetimeEarned' => $account->getLifetimeEarned(),
            'referralCode' => $account->getReferralCode(),
            'createdAt' => $account->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'transactions' => $transactions,
        ];
    }
}
