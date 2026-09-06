<?php

declare(strict_types=1);

namespace App\Tests\Billing\Domain;

use App\Billing\Domain\Exception\InsufficientFundsException;
use App\Billing\Domain\Model\Account;
use App\Billing\Domain\Model\AccountDepositedEvent;
use App\Billing\Domain\Model\Money;
use PHPUnit\Framework\TestCase;

/**
 * Юнит-тест доменной логики.
 * Наследуемся от базового TestCase (чистый PHPUnit, без загрузки Symfony).
 */
final class AccountTest extends TestCase
{
    private const ACCOUNT_ID = 'e7b0b6c2-1a4d-4c3e-8f9a-123456789abc';
    private const USER_ID = 'user-999';

    /**
     * Тест: Баланс должен успешно увеличиваться при пополнении счета,
     * а также должно генерироваться соответствующее доменное событие.
     */
    public function testItDepositsMoneySuccessfullyAndRecordsEvent(): void
    {
        // Arrange (Подготовка)
        $initialBalance = new Money(1000, 'USD'); // 10.00 USD
        $account = new Account(self::ACCOUNT_ID, self::USER_ID, $initialBalance);
        $depositAmount = new Money(500, 'USD');  // 5.00 USD

        // Act (Действие)
        $account->deposit($depositAmount);

        // Assert (Проверка результатов)
        // 1. Проверяем, что итоговый баланс равен 1500 центам (15.00 USD)
        $this->assertSame(1500, $account->getBalance()->getAmount());
        $this->assertSame('USD', $account->getBalance()->getCurrency());

        // 2. Проверяем работу с доменными событиями
        $events = $account->pullEvents();
        $this->assertCount(1, $events, 'Должно быть записано ровно одно доменное событие.');

        /** @var AccountDepositedEvent $event */
        $event = $events[0];
        $this->assertInstanceOf(AccountDepositedEvent::class, $event);
        $this->assertSame(self::ACCOUNT_ID, $event->accountId);
        $this->assertSame(500, $event->amount);
        $this->assertSame('USD', $event->currency);

        // 3. Проверяем, что после pullEvents() внутренний массив событий очистился
        $this->assertCount(0, $account->pullEvents());
    }

    /**
     * Тест: Система должна выбросить исключение InsufficientFundsException,
     * если сумма списания превышает доступный баланс.
     */
    public function testItThrowsExceptionWhenWithdrawingMoreThanAvailableBalance(): void
    {
        // Arrange
        $initialBalance = new Money(1000, 'EUR'); // 10.00 EUR
        $account = new Account(self::ACCOUNT_ID, self::USER_ID, $initialBalance);
        $withdrawAmount = new Money(1001, 'EUR'); // Попытка списать 10.01 EUR

        // Expect (Ожидание исключения)
        $this->expectException(InsufficientFundsException::class);
        // Проверяем, что текст ошибки содержит понятную бизнесу информацию
        $this->expectExceptionMessage('Недостаточно средств на счете "' . self::ACCOUNT_ID . '". Запрошено: 1001, доступно: 1000.');

        // Act
        $account->withdraw($withdrawAmount);
    }
}
