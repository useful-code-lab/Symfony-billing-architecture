<?php

declare(strict_types=1);

namespace App\Billing\Infrastructure\Persistence;

use App\Billing\Domain\Model\Account;
use App\Billing\Domain\Repository\AccountRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Doctrine\DBAL\LockMode; // entityManager->find(Account::class, $id);

final class DoctrineAccountRepository implements AccountRepositoryInterface
{
    // Внедряем event.bus для публикации доменных событий
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $eventBus
    ) {}

    public function getById(string $id): ?Account
    {
        return $this->entityManager->find(Account::class, $id);
    }

        public function getByIdForUpdate(string $id): ?Account
    {
        // Передаем LockMode::PESSIMISTIC_WRITE. 
        // PostgreSQL заблокирует эту строку для чтения/записи другими транзакциями,
        // пока текущая транзакция не завершится (сделает COMMIT или ROLLBACK).
        return $this->entityManager->find(
            className: Account::class, 
            id: $id, 
            lockMode: LockMode::PESSIMISTIC_WRITE
        );
    }

    public function save(Account $account): void
    {
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        // Извлекаем все события, которые успели накопиться в объекте
        $events = $account->pullEvents();

        foreach ($events as $event) {
            // Публикуем каждое событие в шину событий
            $this->eventBus->dispatch($event);
        }
    }
}

