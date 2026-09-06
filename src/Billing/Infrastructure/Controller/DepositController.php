<?php

declare(strict_types=1);

namespace App\Billing\Infrastructure\Controller;

use App\Billing\Application\Command\DepositMoneyCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
final class DepositController
{
    public function __construct(
        private MessageBusInterface $commandBus
    ) {}

    #[Route('/api/v1/accounts/{id}/deposit', name: 'billing_account_deposit', methods: ['POST'])]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        
        // Больше никаких блоков try/catch! Если данные не валидны или баланс нарушен,
        // выброшенное исключение автоматически обработается в ExceptionListener.
        $command = new DepositMoneyCommand(
            accountId: $id,
            amountInCents: (int) ($payload['amount'] ?? 0),
            currency: (string) ($payload['currency'] ?? '')
        );

        $this->commandBus->dispatch($command);

        return new JsonResponse(['status' => 'Command dispatched successfully'], Response::HTTP_ACCEPTED);
    }
}

