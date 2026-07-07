<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\SyliusLoyaltyPlugin\Event\Trigger\CustomerBirthdayTriggerEvent;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Fires the built-in customer_birthday trigger for every customer whose birthday is today
 * (run daily). The trigger's source identifier ("birthday:<year>") makes re-runs on the same
 * day no-ops. Customers born on February 29th are only matched in leap years.
 */
#[AsCommand(
    name: 'setono:sylius-loyalty:trigger-birthdays',
    description: 'Fires the customer_birthday earning trigger for customers whose birthday is today',
)]
final class TriggerBirthdaysCommand extends Command
{
    /**
     * @param class-string $customerClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly string $customerClass,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Customers processed per batch', '100');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $batchSize = max(1, (int) $input->getOption('batch-size'));

        $today = new \DateTimeImmutable('today');
        $year = (int) $today->format('Y');

        $dispatched = 0;
        $offset = 0;
        while (true) {
            $customerIds = $this->customerIds($today, $batchSize, $offset);
            if ([] === $customerIds) {
                break;
            }

            foreach ($customerIds as $customerId) {
                $customer = $this->entityManager->find($this->customerClass, $customerId);
                if (!$customer instanceof CustomerInterface) {
                    continue;
                }

                $this->eventDispatcher->dispatch(new CustomerBirthdayTriggerEvent($customer, $year));
                ++$dispatched;
            }

            $this->entityManager->clear();
            $offset += $batchSize;
        }

        $io->success(sprintf('Dispatched %d birthday trigger(s)', $dispatched));

        return Command::SUCCESS;
    }

    /**
     * DQL has no month/day functions, so the candidates are selected with native SQL (the
     * plugin only ever targets MySQL).
     *
     * @return list<int>
     */
    private function customerIds(\DateTimeImmutable $today, int $limit, int $offset): array
    {
        $table = $this->entityManager->getClassMetadata($this->customerClass)->getTableName();

        /** @var list<array{id: int|string}> $rows */
        $rows = $this->entityManager->getConnection()->fetchAllAssociative(
            sprintf(
                'SELECT id FROM %s WHERE birthday IS NOT NULL AND MONTH(birthday) = :month AND DAY(birthday) = :day ORDER BY id ASC LIMIT %d OFFSET %d',
                $table,
                $limit,
                $offset,
            ),
            [
                'month' => (int) $today->format('n'),
                'day' => (int) $today->format('j'),
            ],
        );

        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }
}
