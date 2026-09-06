<?php
declare(strict_types=1);

namespace App\Billing\Domain\Model;

/**
 * Value Object: Деньги.
 * Он неизменяем (Immutable). Любая операция возвращает новый объект.
 */
final class Money
{
    private int $amount;
    private string $currency;

    public function __construct(int $amount, string $currency)
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Сумма денег не может быть отрицательной.');
        }

        if (strlen($currency) !== 3) {
            throw new \InvalidArgumentException('Валюта должна соответствовать стандарту ISO 4217 (3 символа).');
        }

        $this->amount = $amount;
        $this->currency = strtoupper($currency);
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    // Метод сравнения двух объектов-значений
    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    // Операция сложения денег
    public function add(self $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }

    // Операция вычитания денег
    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);
        
        if ($this->amount < $other->amount) {
            throw new \InvalidArgumentException('Недостаточно средств для вычитания.');
        }

        return new self($this->amount - $other->amount, $this->currency);
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(sprintf(
                'Нельзя проводить операции с разными валютами: %s и %s.',
                $this->currency,
                $other->currency
            ));
        }
    }
}
