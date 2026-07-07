<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Fixture\Factory;

use Doctrine\Persistence\ObjectManager;
use Setono\SyliusLoyaltyPlugin\Ledger\LoyaltyLedgerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

/**
 * Creates an account (with its customer and shop user when missing) and writes its ledger
 * history through the real ledger, so balances, lifetime earned, and lots are all genuine.
 * Public API — reusable in host projects' test apps.
 */
final class LoyaltyAccountExampleFactory implements ExampleFactoryInterface
{
    private readonly OptionsResolver $optionsResolver;

    /**
     * @param FactoryInterface<CustomerInterface> $customerFactory
     * @param FactoryInterface<ShopUserInterface> $shopUserFactory
     * @param UserRepositoryInterface<ShopUserInterface> $shopUserRepository
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(
        private readonly LoyaltyAccountProviderInterface $accountProvider,
        private readonly LoyaltyLedgerInterface $ledger,
        private readonly FactoryInterface $customerFactory,
        private readonly FactoryInterface $shopUserFactory,
        private readonly UserRepositoryInterface $shopUserRepository,
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly ObjectManager $objectManager,
    ) {
        $this->optionsResolver = new OptionsResolver();

        $this->optionsResolver
            ->setRequired('email')
            ->setAllowedTypes('email', 'string')
            ->setRequired('channel')
            ->setAllowedTypes('channel', 'string')
            ->setNormalizer('channel', function (Options $options, string $code): ChannelInterface {
                $channel = $this->channelRepository->findOneByCode($code);
                Assert::isInstanceOf($channel, ChannelInterface::class, sprintf('Channel "%s" not found', $code));

                return $channel;
            })
            ->setDefault('password', 'sylius')
            ->setDefault('enabled', true)
            ->setDefault('history', [])
            ->setAllowedTypes('history', 'array')
        ;
    }

    /**
     * @param array<array-key, mixed> $options
     */
    public function create(array $options = []): LoyaltyAccountInterface
    {
        /** @var array{email: string, channel: ChannelInterface, password: string, enabled: bool, history: list<array<string, mixed>>} $options */
        $options = $this->optionsResolver->resolve($options);

        $account = $this->accountProvider->getByCustomerAndChannel(
            $this->customer($options['email'], $options['password']),
            $options['channel'],
        );

        foreach ($options['history'] as $entry) {
            $this->writeHistoryEntry($account, $entry);
        }

        // Disabling happens after the history is written, so disabled sample accounts can
        // still carry a balance
        $account->setEnabled($options['enabled']);

        return $account;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function writeHistoryEntry(LoyaltyAccountInterface $account, array $entry): void
    {
        $type = $entry['type'] ?? null;
        Assert::string($type, 'Every history entry needs a "type"');

        $points = $entry['points'] ?? null;
        Assert::integer($points, 'Every history entry needs integer "points"');

        $expiresAt = null;
        if (isset($entry['expires_at'])) {
            Assert::string($entry['expires_at']);
            $expiresAt = new \DateTimeImmutable($entry['expires_at']);
        }

        switch ($type) {
            case 'earn_action':
                $sourceIdentifier = $entry['source_identifier'] ?? null;
                Assert::string($sourceIdentifier, 'earn_action history entries need a "source_identifier"');
                $this->ledger->earnAction($account, $points, $sourceIdentifier, [], $expiresAt);

                break;
            case 'manual_credit':
                $this->ledger->manualCredit($account, $points, self::reason($entry), self::note($entry));

                break;
            case 'manual_debit':
                $this->ledger->manualDebit($account, $points, self::reason($entry), self::note($entry));

                break;
            default:
                throw new \InvalidArgumentException(sprintf(
                    'Unsupported history entry type "%s" (supported: earn_action, manual_credit, manual_debit)',
                    $type,
                ));
        }
    }

    private function customer(string $email, string $password): CustomerInterface
    {
        $existing = $this->shopUserRepository->findOneByEmail($email);
        if ($existing instanceof ShopUserInterface && $existing->getCustomer() instanceof CustomerInterface) {
            return $existing->getCustomer();
        }

        $customer = $this->customerFactory->createNew();
        $customer->setEmail($email);
        $customer->setFirstName('Loyal');
        $customer->setLastName('Customer');

        $shopUser = $this->shopUserFactory->createNew();
        $shopUser->setCustomer($customer);
        $shopUser->setUsername($email);
        $shopUser->setPlainPassword($password);
        $shopUser->setEnabled(true);

        $this->objectManager->persist($customer);
        $this->objectManager->persist($shopUser);
        $this->objectManager->flush();

        return $customer;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private static function reason(array $entry): string
    {
        $reason = $entry['reason'] ?? 'goodwill';
        Assert::string($reason);

        return $reason;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private static function note(array $entry): string
    {
        $note = $entry['note'] ?? 'Fixture history entry';
        Assert::string($note);

        return $note;
    }
}
