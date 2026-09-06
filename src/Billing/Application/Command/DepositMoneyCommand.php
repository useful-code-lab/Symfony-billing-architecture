<?php

declare(strict_types=1);

namespace App\Billing\Application\Command;

/**
 * Команда на пополнение счета.
 * Используем readonly-классы PHP 8.2+, чтобы гарантировать неизменяемость DTO.
 */
final readonly class DepositMoneyCommand
{
    public function __construct(
        public string $accountId,
        public int $amountInCents,
        public string $currency
    ) {}
}
