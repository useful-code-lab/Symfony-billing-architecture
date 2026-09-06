<?php

declare(strict_types=1);

namespace Billing\Application\Query;

use Billing\Domain\Repository\AccountRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetBalanceHandler
{
    // На этапе инфраструктуры мы можем заменить это на специализированный Query Repository или DBAL Connection
    public function __construct(
        private AccountRepositoryInterface $accountRepository
    ) {}

    public function __invoke(GetBalanceQuery $query): BalanceView
    {
        $account = $this->accountRepository->getById($query->accountId);
        if ($account === null) {
            throw new \InvalidArgumentException('Аккаунт не найден.');
        }

        return new BalanceView(
            accountId: $account->getId(),
            balance: $account->getBalance()->getAmount() / 100, // Конвертация для вывода
            currency: $account->getBalance()->getCurrency()
        );
    }
}
