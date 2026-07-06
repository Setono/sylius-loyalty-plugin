<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Tests\Functional\Tier;

use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Model\TierInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Tests\Functional\Earning\AwardOrderPointsTestCase;

/**
 * Spec §7.3: earning that crosses a threshold upgrades the account within the same request.
 */
final class InlineTierUpgradeTest extends AwardOrderPointsTestCase
{
    /**
     * @test
     */
    public function it_upgrades_the_tier_in_the_same_transaction_as_the_earn(): void
    {
        $container = self::getContainer();
        $entityManager = $this->entityManager();

        $channel = $this->channel();

        $tierFactory = $container->get('setono_sylius_loyalty.factory.tier');
        \assert(is_object($tierFactory) && method_exists($tierFactory, 'createNew'));
        $tier = $tierFactory->createNew();
        \assert($tier instanceof TierInterface);
        $tier->setCode('func_silver');
        $tier->setName('Silver');
        $tier->setChannel($channel);
        $tier->setPosition(1);
        $tier->setThreshold(1000);
        $entityManager->persist($tier);
        $entityManager->flush();

        $customer = $this->customer();

        $accountProvider = $container->get(LoyaltyAccountProviderInterface::class);
        \assert($accountProvider instanceof LoyaltyAccountProviderInterface);
        $account = $accountProvider->getByCustomerAndChannel($customer, $channel);

        $ledger = $container->get(LoyaltyLedgerInterface::class);
        \assert($ledger instanceof LoyaltyLedgerInterface);

        // Below the threshold: no tier
        $ledger->earnAction($account, 500, 'inline-tier:first');
        self::assertNull($account->getTier());

        // Crossing the threshold upgrades within the same call
        $ledger->earnAction($account, 700, 'inline-tier:second');

        self::assertNotNull($account->getTier());
        self::assertSame('func_silver', $account->getTier()->getCode());
    }
}
