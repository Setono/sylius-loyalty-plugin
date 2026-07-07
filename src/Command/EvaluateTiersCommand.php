<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Command;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Tier\TierEvaluatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Nightly tier reconciliation: upgrades apply immediately, downgrades apply immediately or
 * after the program's grace period. Disabled accounts are skipped — their tier stays frozen
 * while disabled.
 */
#[AsCommand(
    name: 'setono:sylius-loyalty:evaluate-tiers',
    description: 'Re-evaluates every enabled account\'s tier, applying downgrades after the grace period',
)]
final class EvaluateTiersCommand extends Command
{
    /**
     * @param class-string<LoyaltyAccountInterface> $accountClass
     */
    public function __construct(
        private readonly TierEvaluatorInterface $tierEvaluator,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $accountClass,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Accounts processed per batch', '100');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $batchSize = max(1, (int) $input->getOption('batch-size'));
        $now = new \DateTimeImmutable();

        $accounts = 0;
        $changed = 0;
        $lastId = 0;
        while (true) {
            /** @var list<LoyaltyAccountInterface> $batch */
            $batch = $this->entityManager->createQueryBuilder()
                ->select('a')
                ->from($this->accountClass, 'a')
                ->andWhere('a.enabled = true')
                ->andWhere('a.id > :lastId')
                ->setParameter('lastId', $lastId)
                ->orderBy('a.id', 'ASC')
                ->setMaxResults($batchSize)
                ->getQuery()
                ->getResult()
            ;

            if ([] === $batch) {
                break;
            }

            foreach ($batch as $account) {
                ++$accounts;
                $lastId = (int) $account->getId();

                $before = $account->getTier();
                $this->tierEvaluator->reconcile($account, $now);
                if ($account->getTier() !== $before) {
                    ++$changed;
                    $io->text(sprintf(
                        'Account %s: %s -> %s',
                        (string) $account->getId(),
                        $before?->getCode() ?? '—',
                        $account->getTier()?->getCode() ?? '—',
                    ));
                }
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        $io->success(sprintf('Evaluated %d account(s), %d tier change(s)', $accounts, $changed));

        return Command::SUCCESS;
    }
}
