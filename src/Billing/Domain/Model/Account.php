<?php

declare(strict_types=1);

namespace Billing\Domain\Model;

use Billing\Domain\Exception\InsufficientFundsException;

class Account
{
    private string $id;
    private string $userId;
    private Money $balance;
    
    /** @var DomainEventInterface[] */
    private array $recordedEvents = []; // Временное хранилище событий

    public function __construct(string $id, string $userId, Money $initialBalance)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->balance = $initialBalance;
    }

    public function deposit(Money $amount): void
    {
        $this->balance = $this->balance->add($amount);
        
        // Фиксируем доменное событие
        $this->recordEvent(new AccountDepositedEvent(
            $this->id,
            $amount->getAmount(),
            $amount->getCurrency()
        ));
    }

    public function withdraw(Money $amount): void
    {
        try {
            $this->balance = $this->balance->subtract($amount);
            // Здесь в будущем можно добавить событие AccountWithdrawnEvent
        } catch (\InvalidArgumentException $e) {
            throw InsufficientFundsException::forAccount($this->id, $amount->getAmount(), $this->balance->getAmount());
        }
    }

    private function recordEvent(DomainEventInterface $event): void
    {
        $this->recordedEvents[] = $event;
    }

    /**
     * Позволяет инфраструктуре забрать накопленные события.
     * @return DomainEventInterface[]
     */
    public function pullEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = []; // Очищаем, чтобы избежать дублирования
        return $events;
    }

    // Геттеры оставляем без изменений...
    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getBalance(): Money { return $this->balance; }
}
