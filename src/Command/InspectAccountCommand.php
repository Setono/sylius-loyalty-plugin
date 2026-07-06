<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Command;

use Setono\SyliusLoyaltyPlugin\Inspector\AccountInspectorInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Repository\CustomerRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Dumps an account's ledger with replay-derived lot states and the invariant check — the
 * scripting/support twin of the admin ledger inspector.
 */
#[AsCommand(
    name: 'setono:sylius-loyalty:inspect-account',
    description: 'Dumps a loyalty account with replay-derived lot states and the invariant check',
)]
final class InspectAccountCommand extends Command
{
    /**
     * @param CustomerRepositoryInterface<CustomerInterface> $customerRepository
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly LoyaltyAccountRepositoryInterface $accountRepository,
        private readonly AccountInspectorInterface $accountInspector,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The customer email')
            ->addArgument('channel', InputArgument::REQUIRED, 'The channel code')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format: txt or json', 'txt')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $customer = $this->customerRepository->findOneBy(['email' => (string) $input->getArgument('email')]);
        if (!$customer instanceof CustomerInterface) {
            $io->error('Customer not found');

            return Command::FAILURE;
        }

        $channel = $this->channelRepository->findOneByCode((string) $input->getArgument('channel'));
        if (!$channel instanceof ChannelInterface) {
            $io->error('Channel not found');

            return Command::FAILURE;
        }

        $account = $this->accountRepository->findOneByCustomerAndChannel($customer, $channel);
        if (null === $account) {
            $io->error('The customer has no loyalty account in this channel');

            return Command::FAILURE;
        }

        $inspection = $this->accountInspector->inspect($account);
        $data = $inspection->toArray();

        if ('json' === $input->getOption('format')) {
            $output->writeln((string) json_encode($data, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR));

            return $inspection->isHealthy() ? Command::SUCCESS : Command::FAILURE;
        }

        /** @var array<string, scalar|null> $accountData */
        $accountData = $data['account'];
        $io->title(sprintf('Loyalty account %s', self::stringify($accountData['id'] ?? '')));
        $io->definitionList(...array_map(
            static fn (string $key, mixed $value): array => [$key => var_export($value, true)],
            array_keys($accountData),
            array_values($accountData),
        ));

        /** @var list<array<string, mixed>> $lots */
        $lots = $data['lots'];
        $io->section('Lots (replay-derived)');
        $io->table(
            ['Lot', 'Points', 'Expires at', 'Remaining', 'Closed by expiration', 'Consumptions'],
            array_map(
                static fn (array $lot): array => [
                    self::stringify($lot['lot'] ?? ''),
                    self::stringify($lot['points'] ?? ''),
                    self::stringify($lot['expiresAt'] ?? 'never'),
                    self::stringify($lot['remaining'] ?? ''),
                    ($lot['closedByExpiration'] ?? false) === true ? 'yes' : 'no',
                    (string) json_encode($lot['consumptions'] ?? []),
                ],
                $lots,
            ),
        );

        foreach ($inspection->errors as $error) {
            $io->error($error);
        }

        foreach ($inspection->warnings as $warning) {
            $io->warning($warning);
        }

        if ($inspection->isHealthy()) {
            $io->success('All ledger invariants hold');
        }

        return $inspection->isHealthy() ? Command::SUCCESS : Command::FAILURE;
    }

    private static function stringify(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
