<?php

declare(strict_types=1);

namespace App\Billing\Application\Command;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Команда на пополнение счета с правилами валидации.
 */
final readonly class DepositMoneyCommand
{
    public function __construct(
        // Проверяем, что передан валидный UUID v4
        #[Assert\NotBlank(message: 'Идентификатор аккаунта не может быть пустым.')]
        #[Assert\Uuid(versions: 'v4', message: 'Идентификатор аккаунта должен быть валидным UUID v4.')]
        public string $accountId,

        // Сумма пополнения на входе должна быть строго больше нуля
        #[Assert\NotBlank(message: 'Сумма не может быть пустой.')]
        #[Assert\GreaterThan(0, message: 'Сумма пополнения должна быть больше нуля.')]
        public int $amountInCents,

        // Валюта должна состоять строго из 3 букв (например, USD, EUR, RUB)
        #[Assert\NotBlank(message: 'Валюта должна быть указана.')]
        #[Assert\Length(exactly: 3, exactMessage: 'Код валюты должен состоять ровно из {{ limit }} символов.')]
        public string $currency
    ) {}
}

