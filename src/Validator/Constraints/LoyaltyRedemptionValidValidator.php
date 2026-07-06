<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Validator\Constraints;

use Setono\SyliusLoyaltyPlugin\Redemption\AppliedPointsProviderInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class LoyaltyRedemptionValidValidator extends ConstraintValidator
{
    public function __construct(
        private readonly AppliedPointsProviderInterface $appliedPointsProvider,
        private readonly LoyaltyAccountRepositoryInterface $accountRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof LoyaltyRedemptionValid) {
            throw new UnexpectedTypeException($constraint, LoyaltyRedemptionValid::class);
        }

        if (!$value instanceof OrderInterface) {
            return;
        }

        $appliedPoints = $this->appliedPointsProvider->getAppliedPoints($value);
        if ($appliedPoints <= 0) {
            return;
        }

        $customer = $value->getCustomer();
        $channel = $value->getChannel();
        if (!$customer instanceof CustomerInterface || null === $channel) {
            return;
        }

        $account = $this->accountRepository->findOneByCustomerAndChannel($customer, $channel);

        if (null === $account || !$account->isEnabled()) {
            $this->context->buildViolation($constraint->accountDisabledMessage)->addViolation();

            return;
        }

        if ($account->getBalance() < $appliedPoints) {
            $this->context->buildViolation($constraint->insufficientBalanceMessage)->addViolation();
        }
    }
}
