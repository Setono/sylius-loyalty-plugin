<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Command;

use Setono\SyliusLoyaltyPlugin\Gdpr\LoyaltyDataEraserInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Webmozart\Assert\Assert;

#[AsCommand(
    name: 'setono:loyalty:erase-customer-data',
    description: 'Permanently erases a customer\'s loyalty accounts and ledger history (GDPR erasure)',
)]
final class EraseCustomerDataCommand extends Command
{
    /**
     * @param RepositoryInterface<CustomerInterface> $customerRepository
     */
    public function __construct(
        private readonly RepositoryInterface $customerRepository,
        private readonly LoyaltyDataEraserInterface $eraser,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The email address of the customer to erase')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Skip the confirmation prompt (for unattended use)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        Assert::string($email);

        $customer = $this->customerRepository->findOneBy(['emailCanonical' => mb_strtolower($email)]);
        if (!$customer instanceof CustomerInterface) {
            $io->error(sprintf('No customer found with email "%s".', $email));

            return Command::FAILURE;
        }

        if (true !== $input->getOption('force') && !$io->confirm(
            sprintf('This permanently deletes all loyalty data for "%s". Continue?', $email),
            false,
        )) {
            $io->warning('Aborted; nothing was erased.');

            return Command::SUCCESS;
        }

        $count = $this->eraser->erase($customer);

        $io->success(sprintf('Erased loyalty data for "%s" (%d account(s)).', $email, $count));

        return Command::SUCCESS;
    }
}
