<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Fixture\Factory;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyProgramInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

/**
 * Public API — reusable in host projects' test apps.
 */
final class LoyaltyProgramExampleFactory implements ExampleFactoryInterface
{
    private readonly OptionsResolver $optionsResolver;

    /**
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly ChannelRepositoryInterface $channelRepository,
    ) {
        $this->optionsResolver = new OptionsResolver();

        $this->optionsResolver
            ->setRequired('channel')
            ->setAllowedTypes('channel', 'string')
            ->setNormalizer('channel', function (Options $options, string $code): ChannelInterface {
                $channel = $this->channelRepository->findOneByCode($code);
                Assert::isInstanceOf($channel, ChannelInterface::class, sprintf('Channel "%s" not found', $code));

                return $channel;
            })
            ->setDefault('award_order_points_at', LoyaltyProgramInterface::AWARD_ORDER_POINTS_AT_PAYMENT_PAID)
            ->setDefault('earning_basis', LoyaltyProgramInterface::EARNING_BASIS_ITEMS_TOTAL)
            ->setDefault('include_taxes', false)
            ->setDefault('rounding', LoyaltyProgramInterface::ROUNDING_FLOOR)
            ->setDefault('redemption_conversion_points', 1)
            ->setDefault('redemption_conversion_amount', 1)
            ->setDefault('min_redeem_points', 500)
            ->setDefault('max_redeem_percent_of_order', 50)
            ->setDefault('points_expiry_days', 365)
            ->setAllowedTypes('points_expiry_days', ['int', 'null'])
            ->setDefault('clawback_policy', LoyaltyProgramInterface::CLAWBACK_POLICY_ALLOW_NEGATIVE)
            ->setDefault('retroactive_guest_points', false)
        ;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function create(array $options = []): LoyaltyProgramInterface
    {
        /** @var array{channel: ChannelInterface, award_order_points_at: string, earning_basis: string, include_taxes: bool, rounding: string, redemption_conversion_points: int, redemption_conversion_amount: int, min_redeem_points: int, max_redeem_percent_of_order: int, points_expiry_days: int|null, clawback_policy: string, retroactive_guest_points: bool} $options */
        $options = $this->optionsResolver->resolve($options);

        $program = $this->programProvider->getByChannel($options['channel']);
        $program->setAwardOrderPointsAt($options['award_order_points_at']);
        $program->setEarningBasis($options['earning_basis']);
        $program->setIncludeTaxes($options['include_taxes']);
        $program->setRounding($options['rounding']);
        $program->setRedemptionConversionPoints($options['redemption_conversion_points']);
        $program->setRedemptionConversionAmount($options['redemption_conversion_amount']);
        $program->setMinRedeemPoints($options['min_redeem_points']);
        $program->setMaxRedeemPercentOfOrder($options['max_redeem_percent_of_order']);
        $program->setPointsExpiryDays($options['points_expiry_days']);
        $program->setClawbackPolicy($options['clawback_policy']);
        $program->setRetroactiveGuestPoints($options['retroactive_guest_points']);

        return $program;
    }
}
