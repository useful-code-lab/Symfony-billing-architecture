<?php

declare(strict_types=1);

namespace App\Billing\Infrastructure\Share;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ValidationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        // Достаем объект команды из обертки Messenger
        $message = $envelope->getMessage();

        // Запускаем валидацию объекта на основе его атрибутов
        $errors = $this->validator->validate($message);

        // Если найдены нарушения правил — прерываем цепочку и выбрасываем исключение
        if (count($errors) > 0) {
            throw new ValidationFailedException($message, $errors);
        }

        // Если всё в порядке — передаем сообщение следующему Middleware в стек
        return $stack->next()->handle($envelope, $stack);
    }
}
