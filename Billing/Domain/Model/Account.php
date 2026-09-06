<?php
declare(strict_types=1);

namespace Billing\Domain\Model;

use Billing\Domain\Exception\InsufficientFundsException;

class Account
{
    private string $id;
    private string $userId;
    private Money $balance;

    public function __construct(string $id, string $userId, Money $initialBalance)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->balance = $initialBalance; // Передаем готовый объект Money
    }

    /**
     * Пополнение счета.
     */
    public function deposit(Money $amount): void
    {
        // Сущность сама делегирует логику сложения объекту Money
        $this->balance = $this->balance->add($amount);
    }

    /**
     * Списание со счета.
     */
    public function withdraw(Money $amount): void
    {
        try {
            $this->balance = $this->balance->subtract($amount);
        } catch (\InvalidArgumentException $e) {
            // Перехватываем техническую ошибку VO и превращаем в понятное бизнес-исключение
            throw InsufficientFundsException::forAccount(
                $this->id,
                $amount->getAmount(),
                $this->balance->getAmount()
            );
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getBalance(): Money
    {
        return $this->balance;
    }
}
