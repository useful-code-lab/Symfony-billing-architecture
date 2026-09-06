<?php
declare(strict_types=1);

namespace App\Billing\Domain\Exception;

class InsufficientFundsException extends \DomainException
{
    public static function forAccount(string $accountId, int $requested, int $available): self
    {
        return new self(sprintf(
            'Недостаточно средств на счете "%s". Запрошено: %d, доступно: %d.',
            $accountId,
            $requested,
            $available
        ));
    }
}
