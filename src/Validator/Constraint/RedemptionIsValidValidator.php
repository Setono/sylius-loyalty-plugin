<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Validator\Constraint;

use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyAccountProviderInterface;
use Setono\SyliusLoyaltyPlugin\Redemption\RedemptionAdjustments;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class RedemptionIsValidValidator extends ConstraintValidator
{
    public function __construct(
        private readonly LoyaltyAccountProviderInterface $accountProvider,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof RedemptionIsValid) {
            throw new UnexpectedTypeException($constraint, RedemptionIsValid::class);
        }

        if (!$value instanceof OrderInterface) {
            return;
        }

        $points = RedemptionAdjustments::points($value);
        if ($points <= 0) {
            return;
        }

        $customer = $value->getCustomer();
        $channel = $value->getChannel();
        if (!$customer instanceof CustomerInterface || null === $channel) {
            return;
        }

        $account = $this->accountProvider->getAccount($customer, $channel);
        if (!$account->isEnabled()) {
            $this->context->buildViolation($constraint->accountDisabledMessage)->addViolation();

            return;
        }

        if ($account->getBalance() < $points) {
            $this->context->buildViolation($constraint->insufficientBalanceMessage)->addViolation();
        }
    }
}
