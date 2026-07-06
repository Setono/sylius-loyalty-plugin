<?php

declare(strict_types=1);

namespace Setono\SyliusLoyaltyPlugin\Controller\Action\Admin;

use Setono\SyliusLoyaltyPlugin\Exception\InvalidExpressionException;
use Setono\SyliusLoyaltyPlugin\Expression\ExpressionValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The admin-only XHR endpoint behind the expression editor's inline linting. Runs the same
 * server-side parse + whitelist used on save, so the editor can never accept what the save
 * rejects.
 */
final class LintExpressionAction
{
    public function __construct(
        private readonly ExpressionValidatorInterface $validator,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        /** @var mixed $payload */
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['diagnostics' => [['message' => 'Invalid request']]], Response::HTTP_BAD_REQUEST);
        }

        $expression = $payload['expression'] ?? null;
        if (!is_string($expression) || '' === trim($expression)) {
            return new JsonResponse(['diagnostics' => []]);
        }

        $trigger = $payload['trigger'] ?? null;
        $trigger = is_string($trigger) && '' !== $trigger ? $trigger : null;

        try {
            $this->validator->validate($expression, $trigger);
        } catch (InvalidExpressionException $e) {
            return new JsonResponse(['diagnostics' => [['message' => $e->getMessage()]]]);
        }

        return new JsonResponse(['diagnostics' => []]);
    }
}
