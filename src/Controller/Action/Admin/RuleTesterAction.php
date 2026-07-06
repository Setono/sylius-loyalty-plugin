<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Controller\Action\Admin;

use Setono\SyliusLoyaltyPlugin\EarningRule\Basis\EligibleBasisCalculatorInterface;
use Setono\SyliusLoyaltyPlugin\EarningRule\EarningContext;
use Setono\SyliusLoyaltyPlugin\EarningRule\EarningRuleEvaluatorInterface;
use Setono\SyliusLoyaltyPlugin\EarningRule\EvaluationResult;
use Setono\SyliusLoyaltyPlugin\Model\EarningRuleInterface;
use Setono\SyliusLoyaltyPlugin\Provider\LoyaltyProgramProviderInterface;
use Setono\SyliusLoyaltyPlugin\Repository\EarningRuleRepositoryInterface;
use Setono\SyliusLoyaltyPlugin\Repository\LoyaltyAccountRepositoryInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * The rule tester: pick a recent order and see every rule evaluated — matched or not, which
 * conditions failed, computed points, multipliers, the final award, and which scoped rule
 * claimed each item. An evaluation date/time override previews scheduled rules before their
 * window opens. Read-only — never writes (dry-run diversion is bypassed here too).
 */
final class RuleTesterAction
{
    /**
     * @param OrderRepositoryInterface<OrderInterface> $orderRepository
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly EarningRuleRepositoryInterface $ruleRepository,
        private readonly LoyaltyAccountRepositoryInterface $accountRepository,
        private readonly LoyaltyProgramProviderInterface $programProvider,
        private readonly EligibleBasisCalculatorInterface $basisCalculator,
        private readonly EarningRuleEvaluatorInterface $evaluator,
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $orderNumber = trim((string) $request->query->get('order', ''));
        $evaluateAt = trim((string) $request->query->get('evaluate_at', ''));

        $error = null;
        $order = null;
        $result = null;

        if ('' !== $orderNumber) {
            [$order, $result, $error] = $this->evaluate($orderNumber, $evaluateAt);
        }

        return new Response($this->twig->render('@SetonoSyliusLoyaltyPlugin/admin/rule_tester/index.html.twig', [
            'orderNumber' => $orderNumber,
            'evaluateAt' => $evaluateAt,
            'order' => $order,
            'result' => $result,
            'error' => $error,
        ]));
    }

    /**
     * @return array{0: OrderInterface|null, 1: EvaluationResult|null, 2: string|null}
     */
    private function evaluate(string $orderNumber, string $evaluateAt): array
    {
        $order = $this->orderRepository->findOneBy(['number' => $orderNumber]);
        if (!$order instanceof OrderInterface) {
            return [null, null, 'setono_sylius_loyalty.ui.tester_order_not_found'];
        }

        $customer = $order->getCustomer();
        $channel = $order->getChannel();
        if (!$customer instanceof CustomerInterface || null === $channel) {
            return [$order, null, 'setono_sylius_loyalty.ui.tester_order_has_no_customer'];
        }

        $now = null;
        if ('' !== $evaluateAt) {
            try {
                $now = new \DateTimeImmutable($evaluateAt);
            } catch (\Exception) {
                return [$order, null, 'setono_sylius_loyalty.ui.tester_invalid_date'];
            }
        }

        $program = $this->programProvider->getByChannel($channel);
        $basis = $this->basisCalculator->calculate($order, $program);

        $context = new EarningContext(
            channel: $channel,
            customer: $customer,
            account: $this->accountRepository->findOneByCustomerAndChannel($customer, $channel),
            order: $order,
            itemAmounts: $basis->itemAmounts,
            now: $now,
            extraAmount: $basis->extraAmount,
        );

        $result = $this->evaluator->evaluate(
            $this->ruleRepository->findForEvaluation($channel, EarningRuleInterface::TRIGGER_ORDER_ELIGIBLE),
            $context,
            $program,
        );

        return [$order, $result, null];
    }
}
