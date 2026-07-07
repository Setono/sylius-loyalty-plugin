<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Command;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\ReferralInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Repository\ReferralRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Daily referral maintenance: referrals still pending after the program's expiry window
 * become terminal 'expired' (the once-per-channel unique stands — expiry does not reopen
 * referrability), and registration IP hashes older than 90 days are purged (GDPR).
 */
#[AsCommand(
    name: 'setono:sylius-loyalty:expire-referrals',
    description: 'Expires stale pending referrals and purges old registration IP hashes',
)]
final class ExpireReferralsCommand extends Command
{
    public function __construct(
        private readonly ReferralRepositoryInterface $referralRepository,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Referrals processed per batch', '100');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $batchSize = max(1, (int) $input->getOption('batch-size'));
        $now = new \DateTimeImmutable();

        $expired = 0;
        // The expiry window is per channel program; iterate pending referrals and compare
        // against their own channel's setting
        while (true) {
            $batch = $this->referralRepository->findPendingOlderThan($now->modify('-1 day'), $batchSize);
            $expiredInBatch = 0;
            foreach ($batch as $referral) {
                $channel = $referral->getChannel();
                $createdAt = $referral->getCreatedAt();
                if (!$channel instanceof ChannelInterface || null === $createdAt) {
                    continue;
                }

                $days = $this->programProvider->getByChannel($channel)->getReferralPendingExpiryDays();
                if ($createdAt->modify(sprintf('+%d days', $days)) <= $now) {
                    $referral->setStatus(ReferralInterface::STATUS_EXPIRED);
                    ++$expired;
                    ++$expiredInBatch;
                }
            }

            $this->entityManager->flush();

            if (0 === $expiredInBatch || count($batch) < $batchSize) {
                break;
            }
        }

        $purged = 0;
        while (true) {
            $batch = $this->referralRepository->findWithIpHashOlderThan($now->modify('-90 days'), $batchSize);
            if ([] === $batch) {
                break;
            }

            foreach ($batch as $referral) {
                $referral->setRegistrationIpHash(null);
                ++$purged;
            }

            $this->entityManager->flush();
        }

        $io->success(sprintf('%d referral(s) expired, %d IP hash(es) purged', $expired, $purged));

        return Command::SUCCESS;
    }
}
