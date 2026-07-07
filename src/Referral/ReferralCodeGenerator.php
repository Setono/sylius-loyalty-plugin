<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Referral;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusLoyaltyPlugin\Model\LoyaltyAccountInterface;

/**
 * Short, unambiguous referral codes: 8 chars of Crockford base32 (no I, L, O, U), assigned
 * lazily the first time an account's code is needed.
 */
final class ReferralCodeGenerator implements ReferralCodeGeneratorInterface
{
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private const LENGTH = 8;

    /**
     * @param class-string<LoyaltyAccountInterface> $accountClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $accountClass,
    ) {
    }

    public function getCode(LoyaltyAccountInterface $account): string
    {
        $code = $account->getReferralCode();
        if (null !== $code) {
            return $code;
        }

        $repository = $this->entityManager->getRepository($this->accountClass);
        do {
            $code = self::generate();
        } while (null !== $repository->findOneBy(['referralCode' => $code]));

        $account->setReferralCode($code);
        $this->entityManager->flush();

        return $code;
    }

    private static function generate(): string
    {
        $code = '';
        for ($i = 0; $i < self::LENGTH; ++$i) {
            $code .= self::ALPHABET[random_int(0, 31)];
        }

        return $code;
    }
}
