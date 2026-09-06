<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Controller;

use Billing\Application\Query\GetBalanceQuery;
use Billing\Application\Query\BalanceView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
final class GetBalanceController
{
    // HandleTrait предоставляет метод $this->handle(), облегчающий синхронный вызов шины
    use HandleTrait;

    public function __construct(
        MessageBusInterface $queryBus
    ) {
        // Связываем HandleTrait с конкретной шиной запросов
        $this->messageBus = $queryBus;
    }

    #[Route('/api/v1/accounts/{id}/balance', name: 'billing_account_get_balance', methods: ['GET'])]
    public function __invoke(string $id): JsonResponse
    {
        try {
            // 1. Отправляем запрос в шину и ждем возвращаемый DTO (BalanceView)
            /** @var BalanceView $view */
            $view = $this->handle(new GetBalanceQuery($id));

            // 2. Формируем чистый JSON ответ
            return new JsonResponse([
                'account_id' => $view->accountId,
                'balance' => $view->balance,
                'currency' => $view->currency
            ], Response::HTTP_OK);

        } catch (\Throwable $exception) {
            return new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
