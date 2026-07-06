<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Command;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Ledger\LotReplayerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyTransactionRepositoryInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The scheduled aggregation behind the dashboard's outstanding-liability widget: per channel,
 * the sum of every account's replay-derived open-lot remainders, snapshotted onto the
 * program row. Never computed live.
 */
#[AsCommand(
    name: 'setono:sylius-loyalty:calculate-liability',
    description: 'Aggregates outstanding points liability per channel onto the program',
)]
final class CalculateLiabilityCommand extends Command
{
    /**
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     * @param class-string<LoyaltyAccountInterface> $accountClass
     */
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly LoyaltyTransactionRepositoryInterface $transactionRepository,
        private readonly LotReplayerInterface $lotReplayer,
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

        /** @var iterable<ChannelInterface> $channels */
        $channels = $this->channelRepository->findAll();
        foreach ($channels as $channel) {
            $liability = 0;
            $lastId = 0;
            while (true) {
                /** @var list<LoyaltyAccountInterface> $batch */
                $batch = $this->entityManager->createQueryBuilder()
                    ->select('a')
                    ->from($this->accountClass, 'a')
                    ->andWhere('a.channel = :channel')
                    ->andWhere('a.id > :lastId')
                    ->setParameter('channel', $channel)
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
                    $lastId = (int) $account->getId();
                    $replay = $this->lotReplayer->replay($this->transactionRepository->findForReplay($account));
                    foreach ($replay->getOpenLots() as $lotState) {
                        $liability += $lotState->getRemaining();
                    }
                }

                $this->entityManager->clear();
            }

            $program = $this->programProvider->getByChannel($channel);
            $program->setLiabilityPoints($liability);
            $program->setLiabilityCalculatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            $io->text(sprintf('%s: %d points outstanding', (string) $channel->getCode(), $liability));
        }

        $io->success('Liability snapshots updated');

        return Command::SUCCESS;
    }
}
