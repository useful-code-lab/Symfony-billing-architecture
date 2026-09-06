<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Persistence;

use Billing\Domain\Model\Account;
use Billing\Domain\Repository\AccountRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineAccountRepository implements AccountRepositoryInterface
{
    // Внедряем стандартный EntityManager от Doctrine
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function getById(string $id): ?Account
    {
        // Ищем доменный объект по его первичному ключу
        return $this->entityManager->find(Account::class, $id);
    }

    public function save(Account $account): void
    {
        // Doctrine сама отследит изменения в объекте благодаря Unit of Work.
        // Если объект новый — мы добавляем его в менеджер.
        $this->entityManager->persist($account);
        
        // В микросервисной архитектуре вызов flush() часто выносится в Middleware шины сообщений 
        // или в конец сценария, но для явности процесса фиксируем изменения здесь
        $this->entityManager->flush();
    }
}
