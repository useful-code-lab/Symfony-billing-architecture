<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Controller;

use Billing\Application\Command\DepositMoneyCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
final class DepositController
{
    // Внедряем именно command.bus, используя имя переменной для Symfony Autowiring
    public function __construct(
        private MessageBusInterface $commandBus
    ) {}

    #[Route('/api/v1/accounts/{id}/deposit', name: 'billing_account_deposit', methods: ['POST'])]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        // 1. Десериализуем и валидируем сырые входные данные
        $payload = json_decode($request->getContent(), true) ?? [];
        
        $amount = $payload['amount'] ?? null;
        $currency = $payload['currency'] ?? null;

        if ($amount === null || $currency === null) {
            return new JsonResponse(['error' => 'Не переданы обязательные поля amount или currency'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // 2. Формируем команду (DTO)
            $command = new DepositMoneyCommand(
                accountId: $id,
                amountInCents: (int) $amount,
                currency: (string) $currency
            );

            // 3. Отправляем в шину команд
            $this->commandBus->dispatch($command);

            // Возвращаем 202 Accepted. Это семантически верно, так как команда принята на обработку
            return new JsonResponse(['status' => 'Command dispatched successfully'], Response::HTTP_ACCEPTED);

        } catch (\Throwable $exception) {
            // Базовый перехват ошибок. На следующем шаге мы сделаем глобальный Exception Listener
            return new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
