<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Command;

use Setono\SyliusLoyaltyPlugin\Earning\ActionPointsAwarderInterface;
use Setono\SyliusLoyaltyPlugin\Earning\TriggerChannelResolverInterface;
use Setono\SyliusLoyaltyPlugin\Provider\BirthdayCustomerProviderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'setono:loyalty:award-birthday-points',
    description: 'Awards the customer_birthday earning rules to customers whose birthday is today',
)]
final class AwardBirthdayPointsCommand extends Command
{
    public const TRIGGER = 'customer_birthday';

    public function __construct(
        private readonly BirthdayCustomerProviderInterface $customerProvider,
        private readonly TriggerChannelResolverInterface $channelResolver,
        private readonly ActionPointsAwarderInterface $awarder,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $today = new \DateTimeImmutable();
        $year = (int) $today->format('Y');

        $awarded = 0;
        foreach ($this->customerProvider->getCustomersWithBirthday($today) as $customer) {
            foreach ($this->channelResolver->resolve($customer) as $channel) {
                // The source identifier carries the year, so a customer earns once per birthday per year.
                $this->awarder->award(
                    $customer,
                    $channel,
                    self::TRIGGER,
                    sprintf('%s:%d:%d:%d', self::TRIGGER, (int) $customer->getId(), (int) $channel->getId(), $year),
                );
                ++$awarded;
            }
        }

        $io->success(sprintf('Awarded birthday points on %d customer-channel(s).', $awarded));

        return Command::SUCCESS;
    }
}
