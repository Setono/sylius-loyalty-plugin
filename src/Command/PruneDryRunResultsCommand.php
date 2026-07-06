<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Prunes dry-run audit rows older than the retention period (run daily).
 */
#[AsCommand(
    name: 'setono:sylius-loyalty:prune-dry-run-results',
    description: 'Prunes dry-run audit results older than the retention period',
)]
final class PruneDryRunResultsCommand extends Command
{
    /**
     * @param class-string $dryRunResultClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $dryRunResultClass,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('days', null, InputOption::VALUE_REQUIRED, 'Retention period in days', '30');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = max(1, (int) $input->getOption('days'));

        /** @var mixed $pruned */
        $pruned = $this->entityManager->createQueryBuilder()
            ->delete($this->dryRunResultClass, 'd')
            ->andWhere('d.createdAt < :cutoff')
            ->setParameter('cutoff', new \DateTimeImmutable(sprintf('-%d days', $days)))
            ->getQuery()
            ->execute()
        ;

        $io->success(sprintf(
            'Pruned %d dry-run result(s) older than %d days',
            is_numeric($pruned) ? (int) $pruned : 0,
            $days,
        ));

        return Command::SUCCESS;
    }
}
