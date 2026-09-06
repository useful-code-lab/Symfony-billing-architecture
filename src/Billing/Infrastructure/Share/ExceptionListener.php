<?php

declare(strict_types=1);

namespace App\Billing\Infrastructure\Share;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

final class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // 1. Если ошибка пришла из Symfony Messenger, достаем оригинальное доменное исключение
        if ($exception instanceof HandlerFailedException && $exception->getPrevious() !== null) {
            $exception = $exception->getPrevious();
        }

        if ($exception instanceof ValidationFailedException) {
            $errors = [];
            
            // Проходимся по всем нарушениям и формируем плоский список ошибок для API
            foreach ($exception->getViolations() as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            $response = new JsonResponse([
                'error' => [
                    'code' => JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'Ошибка валидации входных данных.',
                    'violations' => $errors
                ]
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);

            $event->setResponse($response);
            return;
        }



        // 2. Определяем HTTP-статус на основе нашей карты маппинга
        $statusCode = DomainExceptionMap::getStatusCode($exception);

        // 3. Формируем единый формат ответа для API (RFC 7807 / Problem Details или кастомный)
        $responseData = [
            'error' => [
                'code' => $statusCode,
                'message' => $exception->getMessage(),
            ]
        ];

        // Логирование критических ошибок уровня 500 (в продакшене здесь должен быть полноценный Logger)
        if ($statusCode === JsonResponse::HTTP_INTERNAL_SERVER_ERROR) {
            $responseData['error']['message'] = 'Внутренняя ошибка сервера. Пожалуйста, обратитесь в поддержку.';
            // Здесь вызывается $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
        }

        // 4. Подменяем стандартный HTML-ответ Symfony на наш JsonResponse
        $response = new JsonResponse($responseData, $statusCode);
        $event->setResponse($response);
    }
}
