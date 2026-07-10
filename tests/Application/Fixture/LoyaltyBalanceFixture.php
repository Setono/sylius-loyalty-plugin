<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Application\Fixture;

use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class LoyaltyBalanceFixture extends AbstractFixture
{
    /**
     * @param RepositoryInterface<CustomerInterface> $customerRepository
     * @param RepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(
        private readonly LoyaltyAccountProviderInterface $accountProvider,
        private readonly LoyaltyLedgerInterface $ledger,
        private readonly RepositoryInterface $customerRepository,
        private readonly RepositoryInterface $channelRepository,
    ) {
    }

    public function getName(): string
    {
        return 'setono_sylius_loyalty_balance';
    }

    public function load(array $options): void
    {
        /** @var array<array{customer: string, channel: string, points: int}> $balances */
        $balances = $options['balances'];

        foreach ($balances as $balance) {
            $customer = $this->customerRepository->findOneBy(['email' => $balance['customer']]);
            $channel = $this->channelRepository->findOneBy(['code' => $balance['channel']]);
            if (!$customer instanceof CustomerInterface || !$channel instanceof ChannelInterface) {
                continue;
            }

            $account = $this->accountProvider->getAccount($customer, $channel);
            $this->ledger->earnForAction($account, sprintf('fixture:%s:%s', $balance['channel'], $balance['customer']), $balance['points']);
        }
    }

    protected function configureOptionsNode(ArrayNodeDefinition $optionsNode): void
    {
        $optionsNode
            ->children()
                ->arrayNode('balances')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('customer')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('channel')->isRequired()->cannotBeEmpty()->end()
                            ->integerNode('points')->defaultValue(5000)->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
