<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Model\ReferralInterface;
use Setono\SyliusLoyaltyPlugin\Referral\AttributionCookie;
use Setono\SyliusLoyaltyPlugin\Repository\ReferralRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Attribution happens only at registration: a valid cookie creates a pending referral —
 * referrals are new-customers-only by construction. No registration form changes ship.
 */
final class CreateReferralOnRegistrationListener
{
    /**
     * @param RepositoryInterface<LoyaltyAccountInterface> $accountRepository
     * @param FactoryInterface<ReferralInterface> $referralFactory
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly RepositoryInterface $accountRepository,
        private readonly ReferralRepositoryInterface $referralRepository,
        private readonly FactoryInterface $referralFactory,
        private readonly ChannelContextInterface $channelContext,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly bool $registrationIpCheck,
        private readonly string $ipHashSalt,
    ) {
    }

    public function __invoke(GenericEvent $event): void
    {
        $customer = $event->getSubject();
        if (!$customer instanceof CustomerInterface) {
            return;
        }

        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return;
        }

        $code = $request->cookies->get(AttributionCookie::NAME);
        if (!is_string($code) || !AttributionCookie::isValidFormat($code)) {
            return;
        }

        $referrerAccount = $this->accountRepository->findOneBy(['referralCode' => $code]);
        if (!$referrerAccount instanceof LoyaltyAccountInterface) {
            return;
        }

        $channel = $this->channelContext->getChannel();

        // Self-referral through one's own link: silently no referral (the fraud checks also
        // guard the reward path)
        if ($referrerAccount->getCustomer() === $customer) {
            return;
        }

        if (null !== $this->referralRepository->findOneByRefereeAndChannel($customer, $channel)) {
            return;
        }

        $referral = $this->referralFactory->createNew();
        $referral->setReferrerAccount($referrerAccount);
        $referral->setRefereeCustomer($customer);
        $referral->setChannel($channel);
        $referral->setCode($code);

        if ($this->registrationIpCheck && null !== $request->getClientIp()) {
            $referral->setRegistrationIpHash(hash('sha256', $this->ipHashSalt . $request->getClientIp()));
        }

        $this->entityManager->persist($referral);
        $this->entityManager->flush();

        $this->logger->info(sprintf(
            '[Loyalty] Referral created: customer %s referred with code %s',
            (string) $customer->getId(),
            $code,
        ));
    }
}
