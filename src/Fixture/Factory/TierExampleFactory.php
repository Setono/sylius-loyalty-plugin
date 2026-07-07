<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Fixture\Factory;

use Setono\SyliusLoyaltyPlugin\Model\TierInterface;
use Setono\SyliusLoyaltyPlugin\Tier\QualificationBasis\PointsEarnedBasis;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

/**
 * Public API — reusable in host projects' test apps.
 */
final class TierExampleFactory implements ExampleFactoryInterface
{
    private readonly OptionsResolver $optionsResolver;

    /**
     * @param FactoryInterface<TierInterface> $tierFactory
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(
        private readonly FactoryInterface $tierFactory,
        private readonly ChannelRepositoryInterface $channelRepository,
    ) {
        $this->optionsResolver = new OptionsResolver();

        $this->optionsResolver
            ->setRequired('code')
            ->setAllowedTypes('code', 'string')
            ->setRequired('name')
            ->setAllowedTypes('name', 'string')
            ->setRequired('channel')
            ->setAllowedTypes('channel', 'string')
            ->setNormalizer('channel', function (Options $options, string $code): ChannelInterface {
                $channel = $this->channelRepository->findOneByCode($code);
                Assert::isInstanceOf($channel, ChannelInterface::class, sprintf('Channel "%s" not found', $code));

                return $channel;
            })
            ->setDefault('position', 0)
            ->setDefault('enabled', true)
            ->setDefault('qualification_basis', PointsEarnedBasis::CODE)
            ->setRequired('threshold')
            ->setAllowedTypes('threshold', 'int')
            ->setDefault('earning_multiplier', 1.0)
            ->setDefault('color', null)
            ->setDefault('benefits_description', null)
        ;
    }

    /**
     * @param array<array-key, mixed> $options
     */
    public function create(array $options = []): TierInterface
    {
        /** @var array{code: string, name: string, channel: ChannelInterface, position: int, enabled: bool, qualification_basis: string, threshold: int, earning_multiplier: float, color: string|null, benefits_description: string|null} $options */
        $options = $this->optionsResolver->resolve($options);

        $tier = $this->tierFactory->createNew();
        $tier->setCode($options['code']);
        $tier->setName($options['name']);
        $tier->setChannel($options['channel']);
        $tier->setPosition($options['position']);
        $tier->setEnabled($options['enabled']);
        $tier->setQualificationBasis($options['qualification_basis']);
        $tier->setThreshold($options['threshold']);
        $tier->setEarningMultiplier((float) $options['earning_multiplier']);
        $tier->setColor($options['color']);
        $tier->setCurrentLocale('en_US');
        $tier->setFallbackLocale('en_US');
        $tier->setBenefitsDescription($options['benefits_description']);

        return $tier;
    }
}
