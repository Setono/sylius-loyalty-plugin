<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Command;

use Setono\SyliusLoyaltyPlugin\Gdpr\CustomerDataExporterInterface;
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
    name: 'setono:loyalty:export-customer-data',
    description: 'Exports a customer\'s loyalty accounts and full ledger history as JSON (GDPR data access)',
)]
final class ExportCustomerDataCommand extends Command
{
    /**
     * @param RepositoryInterface<CustomerInterface> $customerRepository
     */
    public function __construct(
        private readonly RepositoryInterface $customerRepository,
        private readonly CustomerDataExporterInterface $exporter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The email address of the customer to export')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Write the JSON to this file instead of stdout')
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

        $json = json_encode(
            $this->exporter->export($customer),
            \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR,
        );

        $file = $input->getOption('output');
        if (is_string($file)) {
            file_put_contents($file, $json);
            $io->success(sprintf('Exported loyalty data for "%s" to %s', $email, $file));

            return Command::SUCCESS;
        }

        $output->writeln($json);

        return Command::SUCCESS;
    }
}
