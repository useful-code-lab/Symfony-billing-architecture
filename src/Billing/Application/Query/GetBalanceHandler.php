<?php

declare(strict_types=1);

namespace App\Billing\Application\Query;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetBalanceHandler
{
    // Внедряем интерфейс быстрого чтения
    public function __construct(
        private AccountQueryInterface $accountQuery
    ) {}

    public function __invoke(GetBalanceQuery $query): BalanceView
    {
        // Просто делегируем выполнение оптимизированному DBAL-запросу
        return $this->accountQuery->getBalanceView($query->accountId);
    }
}
