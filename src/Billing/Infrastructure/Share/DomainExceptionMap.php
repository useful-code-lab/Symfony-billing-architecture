<?php

declare(strict_types=1);

namespace App\Billing\Infrastructure\Share;

use App\Billing\Domain\Exception\InsufficientFundsException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

final readonly class DomainExceptionMap
{
    /**
     * Карта соответствия: Класс исключения => HTTP статус-код
     */
    private const MAP = [
        InsufficientFundsException::class => Response::HTTP_CONFLICT, // 409: Конфликт состояния (нет денег)
        \InvalidArgumentException::class  => Response::HTTP_BAD_REQUEST, // 400: Неверный формат данных
        ValidationFailedException::class => Response::HTTP_UNPROCESSABLE_ENTITY, // Код 422
    ];

    public static function getStatusCode(\Throwable $exception): int
    {
        foreach (self::MAP as $exceptionClass => $statusCode) {
            if ($exception instanceof $exceptionClass) {
                return $statusCode;
            }
        }

        // Если это неизвестная системная ошибка (например, упала БД) — возвращаем 500
        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }
}
