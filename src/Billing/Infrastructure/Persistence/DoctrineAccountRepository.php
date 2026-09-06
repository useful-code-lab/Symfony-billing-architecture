<?php

declare(strict_types=1);

namespace App\Billing\Infrastructure\Persistence;

use App\Billing\Domain\Model\Account;
use App\Billing\Domain\Repository\AccountRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

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

