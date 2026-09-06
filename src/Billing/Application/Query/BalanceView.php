<?php

declare(strict_types=1);

namespace App\Billing\Application\Query;

final readonly class BalanceView
{
    public function __construct(
        public string $accountId,
        public float $balance, // Для отображения клиенту конвертируем центы в привычный float
        public string $currency
    ) {}
}
