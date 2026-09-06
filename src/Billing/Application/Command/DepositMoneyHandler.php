<?php
declare(strict_types=1);

namespace App\Billing\Application\Command;

use App\Billing\Domain\Model\Money;
use App\Billing\Domain\Repository\AccountRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Атрибут #[AsMessageHandler] автоматически регистрирует этот класс в Symfony Messenger.
 */
#[AsMessageHandler]
final class DepositMoneyHandler
{
    // Внедряем интерфейс репозитория из доменного слоя (Dependency Inversion)
    public function __construct(
        private AccountRepositoryInterface $accountRepository
    ) {}

    /**
     * Точка входа для выполнения команды.
     * Symfony Messenger сам сопоставит класс команды с этим методом по типу аргумента.
     */
    public function __invoke(DepositMoneyCommand $command): void
    {
        // 1. Извлекаем аккаунт из БД (если не найден — выбрасываем исключение)
        $account = $this->accountRepository->getById($command->accountId);
        if ($account === null) {
            throw new \InvalidArgumentException(sprintf('Аккаунт с ID "%s" не найден.', $command->accountId));
        }

        // 2. Создаем доменный Value Object денег из сырых данных команды
        $money = new Money($command->amountInCents, $command->currency);

        // 3. Вызываем бизнес-логику внутри доменной сущности
        $account->deposit($money);

        // 4. Сохраняем обновленный агрегат обратно в репозиторий
        $this->accountRepository->save($account);
    }
}
