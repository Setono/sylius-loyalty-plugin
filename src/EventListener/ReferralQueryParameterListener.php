<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\EventListener;

use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;
use Setono\SyliusLoyaltyPlugin\Referral\AttributionCookie;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Recognizes a referral code as a query parameter on any shop URL (?ref=CODE by default), so
 * "share this product with your code" needs no extra UI. Cheap format check first, existence
 * check second, cookie set on the response; last click wins.
 */
final class ReferralQueryParameterListener
{
    private ?string $capturedCode = null;

    /**
     * @param RepositoryInterface<LoyaltyAccountInterface> $accountRepository
     */
    public function __construct(
        private readonly RepositoryInterface $accountRepository,
        private readonly string $queryParameter,
    ) {
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $code = $event->getRequest()->query->get($this->queryParameter);
        if (!is_string($code)) {
            return;
        }

        $code = strtoupper($code);
        if (!AttributionCookie::isValidFormat($code)) {
            return;
        }

        if (null === $this->accountRepository->findOneBy(['referralCode' => $code])) {
            return;
        }

        $this->capturedCode = $code;
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (null === $this->capturedCode || !$event->isMainRequest()) {
            return;
        }

        $event->getResponse()->headers->setCookie(AttributionCookie::create($this->capturedCode));
        $this->capturedCode = null;
    }
}
