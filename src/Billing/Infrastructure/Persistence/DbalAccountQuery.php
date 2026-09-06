<?php

declare(strict_types=1);

namespace App\Billing\Infrastructure\Persistence;

use App\Billing\Application\Query\AccountQueryInterface;
use App\Billing\Application\Query\BalanceView;
use Doctrine\DBAL\Connection;

final readonly class DbalAccountQuery implements AccountQueryInterface
{
    // Внедряем низкоуровневое подключение к БД вместо EntityManager
    public function __construct(
        private Connection $connection
    ) {}

    public function getBalanceView(string $accountId): BalanceView
    {
        // Пишем оптимизированный сырой SQL-запрос к PostgreSQL.
        // Названия колонок берутся из нашего XML-маппинга Doctrine (Шаг 4).
        $sql = '
            SELECT id, balance_amount, balance_currency 
            FROM billing_accounts 
            WHERE id = :id
            LIMIT 1
        ';

        // Выполняем запрос с безопасной привязкой параметров (защита от SQL-инъекций)
        $row = $this->connection->fetchAssociative($sql, ['id' => $accountId]);

        // Если запись в PostgreSQL не найдена — выбрасываем исключение
        if (!$row) {
            throw new \InvalidArgumentException(sprintf('Аккаунт с ID "%s" не найден.', $accountId));
        }

        // Моментально собираем DTO ответа. Извлекаем данные из плоского ассоциативного массива.
        return new BalanceView(
            accountId: (string) $row['id'],
            balance: (int) $row['balance_amount'] / 100, // Конвертируем центы в float для API
            currency: (string) $row['balance_currency']
        );
    }
}
