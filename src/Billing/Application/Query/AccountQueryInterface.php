<?php

declare(strict_types=1);

namespace App\Billing\Application\Query;

interface AccountQueryInterface
{
    /**
     * Быстрое чтение баланса напрямую из БД.
     * Возвращает плоский DTO для вывода в API.
     */
    public function getBalanceView(string $accountId): BalanceView;
}
